<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Models\FlightPath;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * Tour Constructor — build tours from cities + dates + flights.
 *
 * Flow:
 * 1. Admin picks route name + departure city
 * 2. Admin adds city stays in visit order (city + nights)
 * 3. Admin adds departure dates
 * 4. System derives flight legs from city sequence and finds matching flights
 * 5. Creates FlightPath records for each date where all flights are found
 */
class TourConstructor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static ?string $title = 'Tours';

    protected static ?string $slug = 'tours';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.tour-constructor';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'route_name' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Route')
                        ->schema([
                            TextInput::make('route_name')
                                ->label('Route Name')
                                ->placeholder('Istanbul + Nice')
                                ->required(),
                            Select::make('departure_city_id')
                                ->label('Departure City')
                                ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                ->required(),
                        ])
                        ->columns(2),

                    Section::make('Cities')
                        ->description('Add cities in visit order. System will derive flight legs: Departure → City 1 → City 2 → ... → Departure.')
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
                                ->addActionLabel('+ Add City'),
                        ]),

                    Section::make('Dates')
                        ->description('Add specific departure dates. For each date, the system will look for flights on the correct day offsets.')
                        ->schema([
                            Repeater::make('dates')
                                ->schema([
                                    DatePicker::make('date')
                                        ->label('Departure Date')
                                        ->required(),
                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(30)
                                ->addActionLabel('+ Add Date'),
                        ]),
                ])
                    ->livewireSubmitHandler('generate')
                    ->footer([
                        Actions::make([
                            Action::make('generate')
                                ->label('Generate Tours')
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
        $departureCityId = (int) $data['departure_city_id'];
        $stays = $data['stays'] ?? [];
        $dates = $data['dates'] ?? [];

        if (empty($stays)) {
            Notification::make()->danger()->title('Add at least one city.')->send();
            return;
        }
        if (empty($dates)) {
            Notification::make()->danger()->title('Add at least one departure date.')->send();
            return;
        }

        // Derive flight legs from city sequence: departure → city1 → city2 → ... → departure
        $citySequence = [$departureCityId];
        foreach ($stays as $stay) {
            $citySequence[] = (int) $stay['city_id'];
        }
        $citySequence[] = $departureCityId; // return to departure

        $legs = [];
        $dayOffset = 0;
        for ($i = 0; $i < count($citySequence) - 1; $i++) {
            $legs[] = [
                'from_city_id' => $citySequence[$i],
                'to_city_id' => $citySequence[$i + 1],
                'day_offset' => $dayOffset,
                'direction' => ($i < count($stays)) ? 'outbound' : 'return',
            ];
            // Add nights of current city stay to offset (if it's a stay city, not the last return)
            if ($i < count($stays)) {
                $dayOffset += (int) $stays[$i]['nights'];
            }
        }

        $totalNights = array_sum(array_column($stays, 'nights'));

        // Build airport lookup: city_id → airport_id
        $cityAirport = DB::table('airports')
            ->where('is_active', true)
            ->pluck('id', 'city_id')
            ->toArray();

        // Check all cities have airports
        $allCityIds = array_unique(array_column($legs, 'from_city_id') + array_column($legs, 'to_city_id'));
        $missingAirports = [];
        foreach ($allCityIds as $cid) {
            if (! isset($cityAirport[$cid])) {
                $missingAirports[] = City::find($cid)?->name_en ?? $cid;
            }
        }
        if (! empty($missingAirports)) {
            Notification::make()->danger()
                ->title('No airports found for: ' . implode(', ', $missingAirports))
                ->send();
            return;
        }

        // Index all active flights for quick lookup
        $allFlights = DB::table('flights')->where('is_active', true)->get();
        $flightIndex = [];
        foreach ($allFlights as $f) {
            $key = $f->from_airport_id . '-' . $f->to_airport_id . '-' . $f->departure_date;
            // Keep cheapest if multiple
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

            // Skip if already exists
            if (DB::table('flight_paths')->where('route_name', $routeName)->where('departure_date', $depDate)->exists()) {
                $skippedExists++;
                continue;
            }

            // Find flights for all legs
            $legFlights = [];
            $totalPrice = 0;
            $allFound = true;

            foreach ($legs as $i => $leg) {
                $fromAirport = $cityAirport[$leg['from_city_id']];
                $toAirport = $cityAirport[$leg['to_city_id']];
                $offset = $leg['day_offset'];
                $legDate = date('Y-m-d', strtotime($depDate . " +{$offset} days"));

                $key = $fromAirport . '-' . $toAirport . '-' . $legDate;
                $flight = $flightIndex[$key] ?? null;

                if (! $flight) {
                    $allFound = false;
                    $fromCity = City::find($leg['from_city_id'])?->name_en;
                    $toCity = City::find($leg['to_city_id'])?->name_en;
                    $missingLegs[] = "{$fromCity}→{$toCity} on {$legDate}";
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
                'route_name' => $routeName,
                'departure_date' => $depDate,
                'departure_city_id' => $departureCityId,
                'total_price' => $totalPrice,
                'currency_id' => $usdId,
                'nights' => $totalNights,
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

        // Build result message
        $parts = ["Created {$created} tours for '{$routeName}'."];
        if ($skippedExists > 0) {
            $parts[] = "{$skippedExists} skipped (already exist).";
        }
        if ($skippedNoFlights > 0) {
            $parts[] = "{$skippedNoFlights} skipped (no flights).";
        }
        if (! empty($missingLegs)) {
            $unique = array_unique($missingLegs);
            $parts[] = 'Missing flights: ' . implode(', ', array_slice($unique, 0, 5));
        }

        $notification = $created > 0
            ? Notification::make()->success()
            : Notification::make()->warning();

        $notification->title(implode(' ', $parts))->send();
    }
}
