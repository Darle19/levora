<?php

namespace App\Filament\Resources\TourTemplates\Pages;

use App\Filament\Resources\TourTemplates\TourTemplateResource;
use App\Models\City;
use App\Models\FlightPath;
use App\Models\TourTemplateLeg;
use App\Services\Flights\RapidApiFlightProvider;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class EditTourTemplate extends EditRecord
{
    protected static string $resource = TourTemplateResource::class;

    protected function afterSave(): void
    {
        $this->record->update([
            'total_nights' => $this->record->stays()->sum('nights'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateFlights')
                ->label('Generate Flights')
                ->icon(Heroicon::OutlinedBolt)
                ->color('success')
                ->form([
                    Section::make('Departure Dates')
                        ->description('Generate multiple departure dates. Each leg uses its own source (Local DB or RapidAPI).')
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->live(),
                            TextInput::make('interval_days')
                                ->label('Interval (days)')
                                ->numeric()
                                ->default(7)
                                ->required()
                                ->helperText('7 = weekly, 14 = bi-weekly')
                                ->live(),
                            TextInput::make('count')
                                ->label('Count')
                                ->numeric()
                                ->default(12)
                                ->required()
                                ->minValue(1)
                                ->maxValue(52)
                                ->live(),
                            Placeholder::make('preview')
                                ->label('Dates to generate')
                                ->content(function (Get $get) {
                                    $start = $get('start_date');
                                    $interval = (int) ($get('interval_days') ?: 7);
                                    $count = (int) ($get('count') ?: 1);
                                    if (! $start || $count < 1) return '—';
                                    $dates = [];
                                    for ($i = 0; $i < min($count, 52); $i++) {
                                        $d = strtotime($start . ' +' . ($i * $interval) . ' days');
                                        $dates[] = date('d M Y (D)', $d);
                                    }
                                    return implode(', ', $dates);
                                })
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ])
                ->action(function (array $data) {
                    $this->generateFlightPaths($data);
                }),

            DeleteAction::make(),
        ];
    }

    protected function generateFlightPaths(array $data): void
    {
        $template = $this->record;
        $template->load('stays.city', 'legs.departureCity.airports', 'legs.arrivalCity.airports', 'legs.airline');

        // Build dates from start_date + interval + count
        $startDate = $data['start_date'] ?? null;
        $interval = (int) ($data['interval_days'] ?? 7);
        $count = (int) ($data['count'] ?? 1);

        if (! $startDate || $count < 1) {
            Notification::make()->danger()->title('Set a start date and count.')->send();
            return;
        }

        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = ['date' => date('Y-m-d', strtotime($startDate . ' +' . ($i * $interval) . ' days'))];
        }

        $templateLegs = $template->legs;
        if ($templateLegs->isEmpty()) {
            Notification::make()->danger()->title('No flight legs defined. Add legs to the template first.')->send();
            return;
        }

        // Build route description
        $legNames = $templateLegs->map(function (TourTemplateLeg $leg) {
            $src = $leg->flight_source === 'rapidapi' ? 'API' : 'DB';
            $air = $leg->airline ? $leg->airline->code : '*';
            return $leg->departureCity->name_en . '→' . $leg->arrivalCity->name_en . " [{$air}/{$src}]";
        })->implode(', ');

        // Build airport lookup: city_id → IATA code, city_id → airport_id
        $cityAirportId = [];
        $cityIata = [];
        foreach ($templateLegs as $leg) {
            foreach ([$leg->departureCity, $leg->arrivalCity] as $city) {
                if ($city && $city->airports->isNotEmpty()) {
                    $airport = $city->airports->first();
                    $cityAirportId[$city->id] = $airport->id;
                    $cityIata[$city->id] = $airport->code;
                }
            }
        }

        // Check all cities have airports
        $missingAirports = [];
        foreach ($templateLegs as $leg) {
            if (! isset($cityAirportId[$leg->departure_city_id])) {
                $missingAirports[] = $leg->departureCity->name_en;
            }
            if (! isset($cityAirportId[$leg->arrival_city_id])) {
                $missingAirports[] = $leg->arrivalCity->name_en;
            }
        }
        if (! empty($missingAirports)) {
            Notification::make()->danger()
                ->title('No airports for: ' . implode(', ', array_unique($missingAirports)))
                ->send();
            return;
        }

        // Index local flights for quick lookup
        $allFlights = DB::table('flights')->where('is_active', true)->get();
        $flightIndex = [];
        foreach ($allFlights as $f) {
            $key = $f->from_airport_id . '-' . $f->to_airport_id . '-' . $f->departure_date;
            if (! isset($flightIndex[$key]) || $f->price_adult < $flightIndex[$key]->price_adult) {
                $flightIndex[$key] = $f;
            }
        }

        // Prepare RapidAPI provider (lazy, only if needed)
        $rapidApi = null;
        $needsApi = $templateLegs->contains(fn ($l) => $l->flight_source === 'rapidapi');
        if ($needsApi) {
            $rapidApi = app(RapidApiFlightProvider::class);
        }

        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        $created = 0;
        $skippedExists = 0;
        $skippedNoFlights = 0;
        $missingLegs = [];

        foreach ($dates as $dateEntry) {
            $baseDate = $dateEntry['date'];

            // Skip duplicates
            if (FlightPath::where('tour_template_id', $template->id)
                ->where('departure_date', $baseDate)->exists()) {
                $skippedExists++;
                continue;
            }

            $legResults = [];
            $totalPrice = 0;
            $allFound = true;

            foreach ($templateLegs as $leg) {
                $fromAirportId = $cityAirportId[$leg->departure_city_id];
                $toAirportId = $cityAirportId[$leg->arrival_city_id];
                $fromIata = $cityIata[$leg->departure_city_id];
                $toIata = $cityIata[$leg->arrival_city_id];
                $legDate = $leg->departureDateFor($baseDate);

                $flightId = null;
                $price = null;

                if ($leg->flight_source === 'local_db') {
                    // === LOCAL DB SEARCH ===
                    $key = $fromAirportId . '-' . $toAirportId . '-' . $legDate;
                    $dbFlight = $flightIndex[$key] ?? null;
                    // Filter by airline if specified
                    if ($dbFlight && $leg->airline_id && $dbFlight->airline_id != $leg->airline_id) {
                        // Specific airline requested but cheapest is different — search all
                        $dbFlight = DB::table('flights')
                            ->where('from_airport_id', $fromAirportId)
                            ->where('to_airport_id', $toAirportId)
                            ->where('departure_date', $legDate)
                            ->where('airline_id', $leg->airline_id)
                            ->where('is_active', true)
                            ->orderBy('price_adult')
                            ->first();
                    }
                    if ($dbFlight) {
                        $flightId = $dbFlight->id;
                        $price = (float) $dbFlight->price_adult;
                    }
                } elseif ($leg->flight_source === 'rapidapi' && $rapidApi) {
                    // === RAPIDAPI SEARCH (one-way per leg) ===
                    $airlineCode = $leg->airline?->code;
                    $offers = $rapidApi->search($fromIata, $toIata, $legDate, $leg->passenger_count, $airlineCode);
                    if (! empty($offers)) {
                        $cheapest = $offers[0];
                        $price = $cheapest->priceCents / 100;
                        $flightId = $this->storeApiFlightInDb(
                            $cheapest, $fromAirportId, $toAirportId, $usdId
                        );
                    }
                }

                if ($price === null) {
                    $allFound = false;
                    $src = $leg->flight_source === 'rapidapi' ? 'RapidAPI' : 'Local DB';
                    $missingLegs[] = "{$leg->departureCity->name_en}→{$leg->arrivalCity->name_en} on {$legDate} ({$src})";
                    break;
                }

                $legResults[] = [
                    'flight_id' => $flightId,
                    'price' => $price,
                    'direction' => $leg->leg_order <= count($templateLegs) / 2 ? 'outbound' : 'return',
                    'leg_order' => $leg->leg_order,
                ];
                $totalPrice += $price;
            }

            if (! $allFound) {
                $skippedNoFlights++;
                continue;
            }

            // Create flight path
            $fpId = DB::table('flight_paths')->insertGetId([
                'tour_template_id' => $template->id,
                'route_name' => $template->route_name,
                'departure_date' => $baseDate,
                'departure_city_id' => $template->departure_city_id,
                'total_price' => 0, // calculated dynamically from flight prices
                'currency_id' => $usdId,
                'nights' => $template->total_nights,
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($legResults as $lr) {
                DB::table('flight_path_legs')->insert([
                    'flight_path_id' => $fpId,
                    'flight_id' => $lr['flight_id'],
                    'leg_order' => $lr['leg_order'],
                    'direction' => $lr['direction'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($template->stays as $stay) {
                DB::table('flight_path_stays')->insert([
                    'flight_path_id' => $fpId,
                    'city_id' => $stay->city_id,
                    'stay_order' => $stay->stay_order,
                    'nights' => $stay->nights,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $created++;
        }

        DB::table('cache')->truncate();

        // Notification
        $parts = ["Created {$created} flight paths."];
        $parts[] = "Route: {$legNames}";
        if ($skippedExists > 0) {
            $parts[] = "{$skippedExists} skipped (already exist).";
        }
        if ($skippedNoFlights > 0) {
            $parts[] = "{$skippedNoFlights} skipped (missing flights).";
        }
        if (! empty($missingLegs)) {
            $parts[] = 'Missing: ' . implode(', ', array_unique(array_slice($missingLegs, 0, 5)));
        }

        $notification = $created > 0
            ? Notification::make()->success()
            : Notification::make()->warning();

        $notification->title(implode(' ', $parts))->send();
    }

    /**
     * Store a RapidAPI flight offer into the local flights table for traceability.
     * Returns the new flight ID.
     */
    private function storeApiFlightInDb(
        \App\DTOs\FlightOffer $offer,
        int $fromAirportId,
        int $toAirportId,
        int $currencyId,
    ): int {
        // Find or create airline
        $airlineId = DB::table('airlines')->where('code', $offer->airlineCode)->value('id');
        if (! $airlineId) {
            $airlineId = DB::table('airlines')->insertGetId([
                'code' => $offer->airlineCode ?: 'XX',
                'name' => $offer->airlineCode ?: 'Unknown',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $depDate = $offer->departureAt->format('Y-m-d');
        $depTime = $offer->departureAt->format('H:i:s');
        $arrDate = $offer->arrivalAt->format('Y-m-d');
        $arrTime = $offer->arrivalAt->format('H:i:s');
        $price = $offer->priceCents / 100;

        // Check if flight already exists (same route + date + number)
        $existing = DB::table('flights')
            ->where('from_airport_id', $fromAirportId)
            ->where('to_airport_id', $toAirportId)
            ->where('departure_date', $depDate)
            ->where('flight_number', $offer->flightNumber ?: 'API')
            ->first();

        if ($existing) {
            // Update price if API price is newer/cheaper
            if ($price < (float) $existing->price_adult) {
                DB::table('flights')->where('id', $existing->id)->update([
                    'price_adult' => $price,
                    'updated_at' => now(),
                ]);
            }
            return $existing->id;
        }

        return DB::table('flights')->insertGetId([
            'airline_id' => $airlineId,
            'from_airport_id' => $fromAirportId,
            'to_airport_id' => $toAirportId,
            'origin_city_id' => DB::table('airports')->where('id', $fromAirportId)->value('city_id'),
            'destination_city_id' => DB::table('airports')->where('id', $toAirportId)->value('city_id'),
            'currency_id' => $currencyId,
            'flight_number' => $offer->flightNumber ?: 'API',
            'departure_date' => $depDate,
            'departure_time' => $depTime,
            'arrival_date' => $arrDate,
            'arrival_time' => $arrTime,
            'price_adult' => $price,
            'available_seats' => $offer->seatsAvailable,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\TemplateFlightPathsTable::make(['record' => $this->getRecord()]),
        ];
    }
}
