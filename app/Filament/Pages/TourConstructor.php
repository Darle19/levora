<?php

namespace App\Filament\Pages;

use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Flight;
use App\Models\FlightPath;
use App\Models\Hotel;
use App\Models\MealType;
use App\Models\Setting;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * Tour Constructor — build flight paths from city pairs + flights.
 *
 * Admin picks: route legs (city A → city B + flight), stays (city + nights).
 * System creates FlightPath records for each departure date.
 */
class TourConstructor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static ?string $title = 'Tour Constructor';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.tour-constructor';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'route_name' => '',
            'nights' => 7,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Route Info')
                        ->schema([
                            TextInput::make('route_name')
                                ->label('Route Name')
                                ->placeholder('Istanbul + Nice')
                                ->required(),
                            Select::make('departure_city_id')
                                ->label('Departure City')
                                ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                ->required(),
                            TextInput::make('nights')
                                ->label('Total Nights')
                                ->numeric()
                                ->default(7)
                                ->required(),
                        ])
                        ->columns(3),

                    Section::make('Flight Legs')
                        ->description('Add each flight segment. All flights include baggage.')
                        ->schema([
                            Repeater::make('legs')
                                ->schema([
                                    Select::make('from_city_id')
                                        ->label('From City')
                                        ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                        ->required()
                                        ->live(),
                                    Select::make('to_city_id')
                                        ->label('To City')
                                        ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                        ->required()
                                        ->live(),
                                    Select::make('direction')
                                        ->label('Direction')
                                        ->options(['outbound' => '→ Outbound', 'return' => '← Return'])
                                        ->default('outbound')
                                        ->required(),
                                    TextInput::make('day_offset')
                                        ->label('Day offset from departure')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('0 = departure day, 2 = day+2, etc.')
                                        ->required(),
                                ])
                                ->columns(4)
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(6)
                                ->reorderable()
                                ->addActionLabel('+ Add Flight Leg'),
                        ]),

                    Section::make('City Stays')
                        ->description('Hotels are selected by the customer at search time.')
                        ->schema([
                            Repeater::make('stays')
                                ->schema([
                                    Select::make('city_id')
                                        ->label('City')
                                        ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                        ->required(),
                                    TextInput::make('nights')
                                        ->label('Nights')
                                        ->numeric()
                                        ->default(2)
                                        ->required(),
                                ])
                                ->columns(2)
                                ->defaultItems(2)
                                ->minItems(1)
                                ->maxItems(5)
                                ->reorderable()
                                ->addActionLabel('+ Add City Stay'),
                        ]),

                    Section::make('Date Range')
                        ->description('Flight paths will be created for each matching flight date in this range.')
                        ->schema([
                            DatePicker::make('date_from')
                                ->label('From')
                                ->default(now()->format('Y-m-d'))
                                ->required(),
                            DatePicker::make('date_to')
                                ->label('To')
                                ->default(now()->addMonths(3)->format('Y-m-d'))
                                ->required(),
                        ])
                        ->columns(2),
                ])
                    ->livewireSubmitHandler('generate')
                    ->footer([
                        Actions::make([
                            Action::make('generate')
                                ->label('Generate Flight Paths')
                                ->icon(Heroicon::OutlinedBolt)
                                ->color('success')
                                ->submit('generate'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $routeName = $data['route_name'];
        $departureCityId = $data['departure_city_id'];
        $nights = (int) $data['nights'];
        $legs = $data['legs'] ?? [];
        $stays = $data['stays'] ?? [];
        $dateFrom = $data['date_from'];
        $dateTo = $data['date_to'];

        if (empty($legs) || empty($stays)) {
            Notification::make()->danger()->title('Add at least one leg and one stay.')->send();
            return;
        }

        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');

        // For each leg, find matching flights in the date range
        // The first leg's flights define departure dates
        $firstLeg = $legs[0];
        $fromAirportId = DB::table('airports')
            ->whereIn('city_id', [$firstLeg['from_city_id']])
            ->where('is_active', true)
            ->value('id');
        $toAirportId = DB::table('airports')
            ->whereIn('city_id', [$firstLeg['to_city_id']])
            ->where('is_active', true)
            ->value('id');

        if (! $fromAirportId || ! $toAirportId) {
            Notification::make()->danger()->title('No airports found for first leg cities.')->send();
            return;
        }

        // Get first leg flights in date range — these define departure dates
        $firstLegFlights = DB::table('flights')
            ->where('from_airport_id', $fromAirportId)
            ->where('to_airport_id', $toAirportId)
            ->where('is_active', true)
            ->whereBetween('departure_date', [$dateFrom, $dateTo])
            ->orderBy('departure_date')
            ->get();

        if ($firstLegFlights->isEmpty()) {
            Notification::make()->danger()->title('No flights found for first leg in date range.')->send();
            return;
        }

        // Index all flights for quick lookup
        $allFlights = DB::table('flights')
            ->where('is_active', true)
            ->get();
        $flightIndex = [];
        foreach ($allFlights as $f) {
            $key = $f->from_airport_id . '-' . $f->to_airport_id . '-' . $f->departure_date;
            $flightIndex[$key] = $f;
        }

        // Build airport lookup per city
        $cityAirport = DB::table('airports')
            ->where('is_active', true)
            ->pluck('id', 'city_id')
            ->toArray();

        $created = 0;

        foreach ($firstLegFlights as $firstFlight) {
            $depDate = $firstFlight->departure_date;

            // Check if path already exists
            $exists = DB::table('flight_paths')
                ->where('route_name', $routeName)
                ->where('departure_date', $depDate)
                ->exists();
            if ($exists) { continue; }

            // Find flights for all legs
            $legFlights = [];
            $totalPrice = 0;
            $allFound = true;

            foreach ($legs as $i => $leg) {
                $legFromAirport = $cityAirport[$leg['from_city_id']] ?? null;
                $legToAirport = $cityAirport[$leg['to_city_id']] ?? null;
                if (! $legFromAirport || ! $legToAirport) { $allFound = false; break; }

                $offset = (int) ($leg['day_offset'] ?? 0);
                $legDate = date('Y-m-d', strtotime($depDate . " +{$offset} days"));

                $key = $legFromAirport . '-' . $legToAirport . '-' . $legDate;
                $flight = $flightIndex[$key] ?? null;

                if (! $flight) { $allFound = false; break; }

                $legFlights[] = [
                    'flight' => $flight,
                    'direction' => $leg['direction'] ?? 'outbound',
                    'leg_order' => $i + 1,
                ];
                $totalPrice += (float) $flight->price_adult;
            }

            if (! $allFound) { continue; }

            // Create flight path
            $fpId = DB::table('flight_paths')->insertGetId([
                'route_name' => $routeName,
                'departure_date' => $depDate,
                'departure_city_id' => $departureCityId,
                'total_price' => $totalPrice,
                'currency_id' => $usdId,
                'nights' => $nights,
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create legs
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

            // Create stays
            foreach ($stays as $j => $stay) {
                DB::table('flight_path_stays')->insert([
                    'flight_path_id' => $fpId,
                    'city_id' => $stay['city_id'],
                    'stay_order' => $j + 1,
                    'nights' => (int) $stay['nights'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $created++;
        }

        DB::table('cache')->truncate();

        Notification::make()
            ->success()
            ->title("Created {$created} flight paths for '{$routeName}'")
            ->send();
    }
}
