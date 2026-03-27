<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Models\Country;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\MealType;
use App\Models\Resort;
use App\Models\Tour;
use App\Models\TourStay;
use App\Services\TourPricingService;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Tour Constructor — generate tours from route template.
 *
 * Admin selects: stays (city + hotels), flights, date range.
 * System generates all combinations: date × istanbul_hotel × destination_hotel.
 *
 * @property-read Schema $form
 */
class TourConstructor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static ?string $title = 'Tour Constructor';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.tour-constructor';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'adults' => 2,
            'children' => 0,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Route')
                        ->schema([
                            Select::make('departure_city_id')
                                ->label('Departure City')
                                ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                ->required()
                                ->live(),

                            Select::make('country_id')
                                ->label('Destination Country')
                                ->options(Country::where('is_active', true)->pluck('name_en', 'id'))
                                ->required()
                                ->live(),

                            Select::make('meal_type_id')
                                ->label('Meal Plan')
                                ->options(MealType::where('is_active', true)->pluck('code', 'id'))
                                ->required(),
                        ])
                        ->columns(3),

                    Section::make('Stays')
                        ->schema([
                            Repeater::make('stays')
                                ->schema([
                                    Select::make('city_id')
                                        ->label('City')
                                        ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                        ->required()
                                        ->live(),

                                    CheckboxList::make('hotel_ids')
                                        ->label('Hotels (select all to generate)')
                                        ->options(function (Get $get) {
                                            $cityId = $get('city_id');
                                            if (! $cityId) {
                                                return [];
                                            }
                                            $resortIds = Resort::where('city_id', $cityId)->pluck('id');

                                            return Hotel::whereIn('resort_id', $resortIds)
                                                ->where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(fn ($h) => [$h->id => $h->name . ' ($' . $h->price_per_person . '/room)']);
                                        })
                                        ->required()
                                        ->columns(2),

                                    TextInput::make('nights')
                                        ->label('Nights')
                                        ->numeric()
                                        ->required()
                                        ->default(2)
                                        ->minValue(1)
                                        ->maxValue(30),
                                ])
                                ->columns(1)
                                ->defaultItems(2)
                                ->minItems(1)
                                ->maxItems(5)
                                ->reorderable()
                                ->addActionLabel('Add Stay'),
                        ]),

                    Section::make('Flights')
                        ->schema([
                            CheckboxList::make('flight_ids')
                                ->label('Select flights to include')
                                ->options(function (Get $get) {
                                    return Flight::where('is_active', true)
                                        ->where('departure_date', '>=', now()->toDateString())
                                        ->with(['fromAirport', 'toAirport', 'airline'])
                                        ->orderBy('departure_date')
                                        ->get()
                                        ->mapWithKeys(fn ($f) => [
                                            $f->id => $f->fromAirport->code . '→' . $f->toAirport->code
                                                . ' | ' . $f->departure_date->format('d.m.Y')
                                                . ' | ' . $f->airline->name
                                                . ' | $' . number_format($f->price_adult, 0),
                                        ]);
                                })
                                ->columns(1)
                                ->helperText('Tours will be generated for each departure date from the selected flights'),
                        ]),

                    Section::make('Passengers & Dates')
                        ->schema([
                            TextInput::make('adults')
                                ->label('Adults')
                                ->numeric()
                                ->required()
                                ->default(2)
                                ->minValue(1),

                            TextInput::make('children')
                                ->label('Children')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            DatePicker::make('date_from')
                                ->label('Date Range From')
                                ->helperText('Leave empty to use flight dates'),

                            DatePicker::make('date_to')
                                ->label('Date Range To')
                                ->helperText('Leave empty to use flight dates'),
                        ])
                        ->columns(4),
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
        $pricingService = app(TourPricingService::class);

        $stays = $data['stays'] ?? [];
        $flightIds = $data['flight_ids'] ?? [];
        $departureCityId = $data['departure_city_id'];
        $countryId = $data['country_id'];
        $mealTypeId = $data['meal_type_id'];
        $adults = (int) ($data['adults'] ?? 2);
        $children = (int) ($data['children'] ?? 0);

        if (empty($stays)) {
            Notification::make()->danger()->title('Add at least one stay')->send();
            return;
        }

        // Calculate total nights
        $totalNights = collect($stays)->sum('nights');

        // Get selected flights grouped by departure date
        $flights = Flight::whereIn('id', $flightIds)->orderBy('departure_date')->get();

        // Determine departure dates
        $departureDates = $flights->pluck('departure_date')->unique()->sort()->values();

        // If date range specified, filter or generate dates
        if (! empty($data['date_from']) && ! empty($data['date_to'])) {
            $from = \Carbon\Carbon::parse($data['date_from']);
            $to = \Carbon\Carbon::parse($data['date_to']);

            if ($departureDates->isNotEmpty()) {
                $departureDates = $departureDates->filter(fn ($d) => $d->between($from, $to));
            } else {
                // Generate weekly dates in range
                $departureDates = collect();
                $current = $from->copy();
                while ($current->lte($to)) {
                    $departureDates->push($current->copy());
                    $current->addWeek();
                }
            }
        }

        if ($departureDates->isEmpty()) {
            Notification::make()->danger()->title('No departure dates found. Select flights or set date range.')->send();
            return;
        }

        // Build hotel combinations from stays
        // Each stay has multiple hotel_ids — we need cartesian product
        $hotelCombinations = $this->buildHotelCombinations($stays);

        $created = 0;
        $skipped = 0;

        foreach ($departureDates as $departureDate) {
            foreach ($hotelCombinations as $combo) {
                // combo = [['city_id' => X, 'hotel_id' => Y, 'nights' => Z, 'resort_id' => R], ...]
                $primaryHotel = Hotel::find($combo[count($combo) - 1]['hotel_id']); // last stay = destination
                if (! $primaryHotel) {
                    continue;
                }

                // Check if tour already exists
                $exists = Tour::where('date_from', $departureDate->format('Y-m-d'))
                    ->where('hotel_id', $primaryHotel->id)
                    ->where('departure_city_id', $departureCityId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $tour = Tour::create([
                    'date_from' => $departureDate->format('Y-m-d'),
                    'date_to' => $departureDate->copy()->addDays($totalNights)->format('Y-m-d'),
                    'nights' => $totalNights,
                    'adults' => $adults,
                    'children' => $children,
                    'price' => 0,
                    'departure_city_id' => $departureCityId,
                    'country_id' => $countryId,
                    'hotel_id' => $primaryHotel->id,
                    'resort_id' => $primaryHotel->resort_id,
                    'meal_type_id' => $mealTypeId,
                    'transport_type_id' => 1, // Air
                    'currency_id' => 1, // USD
                    'program_type_id' => 1,
                    'is_available' => true,
                    'is_hot' => false,
                ]);

                // Create stays
                foreach ($combo as $order => $stay) {
                    TourStay::create([
                        'tour_id' => $tour->id,
                        'city_id' => $stay['city_id'],
                        'resort_id' => $stay['resort_id'],
                        'hotel_id' => $stay['hotel_id'],
                        'nights' => $stay['nights'],
                        'stay_order' => $order + 1,
                        'meal_type_id' => $mealTypeId,
                    ]);
                }

                // Attach flights for this departure date
                $legOrder = 1;
                foreach ($flights as $flight) {
                    if ($flight->departure_date->format('Y-m-d') === $departureDate->format('Y-m-d')
                        || $flight->departure_date->between($departureDate, $departureDate->copy()->addDays($totalNights))
                    ) {
                        if (! $tour->flights()->where('flight_id', $flight->id)->exists()) {
                            $direction = $legOrder <= intdiv(count($flightIds), 2) + 1 ? 'outbound' : 'return';
                            $tour->flights()->attach($flight->id, [
                                'direction' => $direction,
                                'leg_order' => $legOrder++,
                            ]);
                        }
                    }
                }

                $pricingService->recalculate($tour);
                $created++;
            }
        }

        cache()->forget('tour_filter_options');

        Notification::make()
            ->success()
            ->title("Generated {$created} tours" . ($skipped > 0 ? " ({$skipped} skipped — already exist)" : ''))
            ->send();
    }

    /**
     * Build cartesian product of hotel selections across stays.
     * Input: [['city_id' => 1, 'hotel_ids' => [1,2,3], 'nights' => 2], ...]
     * Output: [[['city_id'=>1,'hotel_id'=>1,'nights'=>2], ['city_id'=>2,'hotel_id'=>5,'nights'=>4]], ...]
     */
    private function buildHotelCombinations(array $stays): array
    {
        $result = [[]];

        foreach ($stays as $stay) {
            $cityId = $stay['city_id'];
            $nights = (int) $stay['nights'];
            $hotelIds = $stay['hotel_ids'] ?? [];
            $newResult = [];

            foreach ($result as $combo) {
                foreach ($hotelIds as $hotelId) {
                    $hotel = Hotel::find($hotelId);
                    $newResult[] = array_merge($combo, [[
                        'city_id' => $cityId,
                        'hotel_id' => $hotelId,
                        'resort_id' => $hotel?->resort_id,
                        'nights' => $nights,
                    ]]);
                }
            }

            $result = $newResult;
        }

        return $result;
    }
}
