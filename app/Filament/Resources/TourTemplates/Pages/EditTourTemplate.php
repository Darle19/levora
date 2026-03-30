<?php

namespace App\Filament\Resources\TourTemplates\Pages;

use App\Filament\Resources\TourTemplates\TourTemplateResource;
use App\Models\City;
use App\Models\FlightPath;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
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
                    Section::make('Flight Source')
                        ->schema([
                            Select::make('source')
                                ->label('Find flights from')
                                ->options([
                                    'db' => 'Local Database',
                                    // 'provider' => 'Flight Provider (RapidAPI)',
                                ])
                                ->default('db')
                                ->required(),
                        ]),
                    Section::make('Departure Dates')
                        ->description('Add dates to generate flight paths for.')
                        ->schema([
                            Repeater::make('dates')
                                ->schema([
                                    DatePicker::make('date')
                                        ->label('Date')
                                        ->required(),
                                ])
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(30)
                                ->addActionLabel('+ Add Date'),
                        ]),
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
        $template->load('stays.city');

        $dates = $data['dates'] ?? [];
        if (empty($dates)) {
            Notification::make()->danger()->title('Add at least one date.')->send();
            return;
        }

        $legs = $template->deriveLegs();
        if (empty($legs)) {
            Notification::make()->danger()->title('No legs could be derived. Add city stays first.')->send();
            return;
        }

        // Show derived route for confirmation
        $legNames = collect($legs)->map(function ($leg) {
            $from = City::find($leg['from_city_id'])?->name_en ?? '?';
            $to = City::find($leg['to_city_id'])?->name_en ?? '?';
            return "{$from}→{$to} (day +{$leg['day_offset']})";
        })->implode(', ');

        // Build airport lookup
        $cityAirport = DB::table('airports')
            ->where('is_active', true)
            ->pluck('id', 'city_id')
            ->toArray();

        // Check airports exist for all cities
        $missingAirports = [];
        foreach ($legs as $leg) {
            foreach (['from_city_id', 'to_city_id'] as $field) {
                if (! isset($cityAirport[$leg[$field]])) {
                    $missingAirports[] = City::find($leg[$field])?->name_en ?? $leg[$field];
                }
            }
        }
        if (! empty($missingAirports)) {
            Notification::make()->danger()
                ->title('No airports for: ' . implode(', ', array_unique($missingAirports)))
                ->send();
            return;
        }

        // Index flights
        $allFlights = DB::table('flights')->where('is_active', true)->get();
        $flightIndex = [];
        foreach ($allFlights as $f) {
            $key = $f->from_airport_id . '-' . $f->to_airport_id . '-' . $f->departure_date;
            if (! isset($flightIndex[$key]) || $f->price_adult < $flightIndex[$key]->price_adult) {
                $flightIndex[$key] = $f;
            }
        }

        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        $created = 0;
        $skippedExists = 0;
        $skippedNoFlights = 0;
        $missingLegs = [];

        foreach ($dates as $dateEntry) {
            $depDate = $dateEntry['date'];

            // Skip duplicates
            if (FlightPath::where('tour_template_id', $template->id)
                ->where('departure_date', $depDate)->exists()) {
                $skippedExists++;
                continue;
            }

            $legFlights = [];
            $totalPrice = 0;
            $allFound = true;

            foreach ($legs as $i => $leg) {
                $fromAirport = $cityAirport[$leg['from_city_id']];
                $toAirport = $cityAirport[$leg['to_city_id']];
                $legDate = date('Y-m-d', strtotime($depDate . " +{$leg['day_offset']} days"));

                $key = $fromAirport . '-' . $toAirport . '-' . $legDate;
                $flight = $flightIndex[$key] ?? null;

                if (! $flight) {
                    $allFound = false;
                    $from = City::find($leg['from_city_id'])?->name_en;
                    $to = City::find($leg['to_city_id'])?->name_en;
                    $missingLegs[] = "{$from}→{$to} on {$legDate}";
                    break;
                }

                $legFlights[] = [
                    'flight' => $flight,
                    'direction' => $leg['direction'],
                    'leg_order' => $i + 1,
                ];
                $totalPrice += (float) $flight->price_adult;
            }

            if (! $allFound) {
                $skippedNoFlights++;
                continue;
            }

            // Create flight path
            $fpId = DB::table('flight_paths')->insertGetId([
                'tour_template_id' => $template->id,
                'route_name' => $template->route_name,
                'departure_date' => $depDate,
                'departure_city_id' => $template->departure_city_id,
                'total_price' => $totalPrice,
                'currency_id' => $usdId,
                'nights' => $template->total_nights,
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($legFlights as $lf) {
                DB::table('flight_path_legs')->insert([
                    'flight_path_id' => $fpId,
                    'flight_id' => $lf['flight']->id,
                    'leg_order' => $lf['leg_order'],
                    'direction' => $lf['direction'],
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
            $parts[] = "{$skippedNoFlights} skipped (no flights found).";
        }
        if (! empty($missingLegs)) {
            $parts[] = 'Missing: ' . implode(', ', array_unique(array_slice($missingLegs, 0, 5)));
        }

        $notification = $created > 0
            ? Notification::make()->success()
            : Notification::make()->warning();

        $notification->title(implode(' ', $parts))->send();
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\TemplateFlightPathsTable::make(['record' => $this->getRecord()]),
        ];
    }
}
