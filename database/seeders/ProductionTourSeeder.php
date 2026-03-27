<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\MealType;
use App\Models\ProgramType;
use App\Models\Resort;
use App\Models\Tour;
use App\Models\TourStay;
use App\Models\TourType;
use App\Models\TransportType;
use App\Services\TourPricingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds real tour data for production.
 * Prices are calculated via TourPricingService from DB hotel/flight rates.
 * Safe to run multiple times — uses firstOrCreate everywhere.
 */
class ProductionTourSeeder extends Seeder
{
    private const ISTANBUL_NIGHTS = 2;
    private const DESTINATION_NIGHTS = 4;
    private const TOTAL_NIGHTS = 7;
    private const DEFAULT_ADULTS = 2;
    private const DEFAULT_CHILDREN = 0;
    private const SEASON_START = '2026-04-01';
    private const SEASON_END = '2026-06-30';

    public function run(): void
    {
        $pricingService = app(TourPricingService::class);

        // ── Reference data ──
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
        $mealBB = MealType::firstOrCreate(['code' => 'BB'], ['name_en' => 'Bed & Breakfast', 'name_ru' => 'Завтрак', 'name_uz' => 'Nonushta', 'is_active' => true]);
        $transportAir = TransportType::firstOrCreate(['name_en' => 'Air'], ['name_ru' => 'Авиа', 'name_uz' => 'Avia', 'is_active' => true]);
        $tourType = TourType::firstOrCreate(['name_en' => 'Combined'], ['name_ru' => 'Комбинированный', 'name_uz' => 'Kombinatsiyalangan', 'is_active' => true]);
        $programType = ProgramType::firstOrCreate(['name_en' => 'Standard'], ['name_ru' => 'Стандарт', 'name_uz' => 'Standart', 'is_active' => true]);
        $star3 = HotelCategory::firstOrCreate(['stars' => 3], ['name_en' => '3 Star', 'name_ru' => '3 звезды', 'name_uz' => '3 yulduz', 'is_active' => true]);

        // ── Countries ──
        $uzbekistan = Country::firstOrCreate(['name_en' => 'Uzbekistan'], ['code' => 'UZ', 'name_ru' => 'Узбекистан', 'name_uz' => 'O\'zbekiston', 'is_active' => true, 'order' => 0]);
        $turkey = Country::firstOrCreate(['name_en' => 'Turkey'], ['code' => 'TR', 'name_ru' => 'Турция', 'name_uz' => 'Turkiya', 'is_active' => true, 'order' => 1]);
        $france = Country::firstOrCreate(['name_en' => 'France'], ['code' => 'FR', 'name_ru' => 'Франция', 'name_uz' => 'Frantsiya', 'is_active' => true, 'order' => 2]);
        $azerbaijan = Country::firstOrCreate(['name_en' => 'Azerbaijan'], ['code' => 'AZ', 'name_ru' => 'Азербайджан', 'name_uz' => 'Ozarbayjon', 'is_active' => true, 'order' => 3]);

        // ── Cities ──
        $tashkent = City::firstOrCreate(['name_en' => 'Tashkent'], ['name_ru' => 'Ташкент', 'name_uz' => 'Toshkent', 'country_id' => $uzbekistan->id, 'is_active' => true, 'order' => 1]);
        $istanbul = City::firstOrCreate(['name_en' => 'Istanbul'], ['name_ru' => 'Стамбул', 'name_uz' => 'Istanbul', 'country_id' => $turkey->id, 'is_active' => true, 'order' => 2]);
        $nice = City::firstOrCreate(['name_en' => 'Nice'], ['name_ru' => 'Ницца', 'name_uz' => 'Nitsa', 'country_id' => $france->id, 'is_active' => true, 'order' => 3]);
        $baku = City::firstOrCreate(['name_en' => 'Baku'], ['name_ru' => 'Баку', 'name_uz' => 'Boku', 'country_id' => $azerbaijan->id, 'is_active' => true, 'order' => 4]);

        // ── Resorts ──
        $sultanahmet = Resort::firstOrCreate(['name_en' => 'Sultanahmet'], ['name_ru' => 'Султанахмет', 'name_uz' => 'Sultanahmet', 'city_id' => $istanbul->id, 'country_id' => $turkey->id, 'is_active' => true, 'order' => 1]);
        $fatih = Resort::firstOrCreate(['name_en' => 'Fatih'], ['name_ru' => 'Фатих', 'name_uz' => 'Fatih', 'city_id' => $istanbul->id, 'country_id' => $turkey->id, 'is_active' => true, 'order' => 2]);
        $niceStade = Resort::firstOrCreate(['name_en' => 'Nice Stade'], ['name_ru' => 'Ницца Стад', 'name_uz' => 'Nice Stade', 'city_id' => $nice->id, 'country_id' => $france->id, 'is_active' => true, 'order' => 1]);
        $bakuBoulevard = Resort::firstOrCreate(['name_en' => 'Baku Boulevard'], ['name_ru' => 'Бакинский бульвар', 'name_uz' => 'Boku bulvari', 'city_id' => $baku->id, 'country_id' => $azerbaijan->id, 'is_active' => true, 'order' => 1]);

        // ── Istanbul Hotels (prices per ROOM dbl with breakfast) ──
        $istanbulHotels = [
            ['name' => 'Grand Liza Hotel', 'pricePerRoom' => 45, 'resort' => $fatih],
            ['name' => 'Grand Emir Hotel', 'pricePerRoom' => 50, 'resort' => $fatih],
            ['name' => 'All Seasons Hotel Istanbul', 'pricePerRoom' => 55, 'resort' => $fatih],
            ['name' => 'New Emin Hotel', 'pricePerRoom' => 61, 'resort' => $sultanahmet],
            ['name' => 'River Hotel', 'pricePerRoom' => 62, 'resort' => $fatih],
            ['name' => 'Grand Washington Hotel', 'pricePerRoom' => 75, 'resort' => $sultanahmet],
            ['name' => 'Sorisso Hotel', 'pricePerRoom' => 75, 'resort' => $sultanahmet],
        ];

        $istHotels = [];
        foreach ($istanbulHotels as $h) {
            $hotel = Hotel::firstOrCreate(
                ['name' => $h['name']],
                [
                    'name_en' => $h['name'], 'name_ru' => $h['name'], 'name_uz' => $h['name'],
                    'description' => 'Hotel in Istanbul', 'address' => 'Istanbul, Turkey',
                    'resort_id' => $h['resort']->id, 'hotel_category_id' => $star3->id,
                    'rating' => 3.5, 'is_active' => true,
                    'price_per_person' => $h['pricePerRoom'], 'currency_id' => $usd->id,
                ]
            );
            $hotel->update(['price_per_person' => $h['pricePerRoom']]);
            $istHotels[] = $hotel;
        }

        // ── Nice Hotel ──
        $niceHotel = Hotel::firstOrCreate(
            ['name' => 'B&B HOTEL Nice Stade Riviera 3 étoiles'],
            [
                'name_en' => 'B&B HOTEL Nice Stade Riviera', 'name_ru' => 'B&B HOTEL Nice Stade Riviera', 'name_uz' => 'B&B HOTEL Nice Stade Riviera',
                'description' => 'Hotel in Nice', 'address' => 'Nice, France',
                'resort_id' => $niceStade->id, 'hotel_category_id' => $star3->id,
                'rating' => 3.0, 'is_active' => true,
                'price_per_person' => 110, 'currency_id' => $usd->id,
            ]
        );

        // ── Baku Hotel ──
        $nobelHotel = Hotel::firstOrCreate(
            ['name' => 'Nobel Hotel'],
            [
                'name_en' => 'Nobel Hotel', 'name_ru' => 'Nobel Hotel', 'name_uz' => 'Nobel Hotel',
                'description' => 'Hotel in Baku', 'address' => 'Baku, Azerbaijan',
                'resort_id' => $bakuBoulevard->id, 'hotel_category_id' => $star3->id,
                'rating' => 3.5, 'is_active' => true,
                'price_per_person' => 50, 'currency_id' => $usd->id,
            ]
        );
        $nobelHotel->update(['price_per_person' => 50]);

        // ── Airports & Airlines ──
        $tasAirport = Airport::firstOrCreate(['code' => 'TAS'], ['name_en' => 'Tashkent International Airport', 'city_id' => $tashkent->id, 'is_active' => true]);
        $istAirport = Airport::firstOrCreate(['code' => 'IST'], ['name_en' => 'Istanbul Airport', 'city_id' => $istanbul->id, 'is_active' => true]);
        $gydAirport = Airport::firstOrCreate(['code' => 'GYD'], ['name_en' => 'Heydar Aliyev International Airport', 'city_id' => $baku->id, 'is_active' => true]);
        $nceAirport = Airport::firstOrCreate(['code' => 'NCE'], ['name_en' => 'Nice Côte d\'Azur Airport', 'city_id' => $nice->id, 'is_active' => true]);
        $centrumAir = Airline::firstOrCreate(['code' => 'C2'], ['name' => 'Centrum Air', 'is_active' => true]);

        // ── Flights: TAS→IST (outbound) and GYD→TAS (return for Baku route) ──
        $flightSchedule = [
            // [route, from_airport, to_airport, date, soft_block_price, hard_block_price, dep_time, arr_time]
            // TAS→IST outbound
            ['TAS-IST', $tasAirport, $istAirport, '2026-04-13', 215, 215, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-04-20', 215, 215, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-04-27', 215, 215, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-05-04', 220, 220, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-05-11', 220, 220, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-05-18', 220, 220, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-05-25', 220, 220, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-06-01', 230, 230, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-06-08', 230, 230, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-06-15', 230, 230, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-06-22', 230, 230, '08:00', '11:30'],
            ['TAS-IST', $tasAirport, $istAirport, '2026-06-29', 230, 230, '08:00', '11:30'],
            // GYD→TAS return
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-04-20', 180, 180, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-04-27', 180, 180, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-05-04', 180, 180, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-05-11', 180, 180, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-05-18', 180, 180, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-05-25', 180, 180, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-06-01', 190, 190, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-06-08', 190, 190, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-06-15', 190, 190, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-06-22', 190, 190, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-06-29', 190, 190, '14:00', '18:30'],
            ['GYD-TAS', $gydAirport, $tasAirport, '2026-07-06', 190, 190, '14:00', '18:30'],
        ];

        $softBlockSeats = 20;
        $flightsCreated = 0;
        $allFlights = [];

        foreach ($flightSchedule as [$route, $fromAirport, $toAirport, $date, $softPrice, $hardPrice, $depTime, $arrTime]) {
            $flight = Flight::firstOrCreate(
                [
                    'airline_id' => $centrumAir->id,
                    'from_airport_id' => $fromAirport->id,
                    'to_airport_id' => $toAirport->id,
                    'departure_date' => $date,
                ],
                [
                    'flight_number' => 'C2 ' . str_replace('-', '', $route),
                    'departure_time' => $depTime,
                    'arrival_date' => $date,
                    'arrival_time' => $arrTime,
                    'currency_id' => $usd->id,
                    'price_adult' => $softPrice,
                    'soft_block_price' => $softPrice,
                    'hard_block_price' => $hardPrice,
                    'available_seats' => $softBlockSeats,
                    'class_type' => 'economy',
                    'is_active' => true,
                ]
            );
            $allFlights[$route . '-' . $date] = $flight;
            $flightsCreated++;
        }
        $this->command->info("Ensured {$flightsCreated} flights exist.");

        // ── Shared tour defaults ──
        $tourDefaults = [
            'nights' => self::TOTAL_NIGHTS,
            'adults' => self::DEFAULT_ADULTS,
            'children' => self::DEFAULT_CHILDREN,
            'price' => 0, // will be recalculated by TourPricingService
            'meal_type_id' => $mealBB->id,
            'transport_type_id' => $transportAir->id,
            'currency_id' => $usd->id,
            'tour_type_id' => $tourType->id,
            'program_type_id' => $programType->id,
            'is_available' => true,
            'is_hot' => false,
        ];

        // ── Generate Istanbul+Nice Tours (flights: TAS→IST outbound, no return yet) ──
        $niceCount = $this->generateRoute(
            istanbulHotels: $istHotels,
            destinationHotel: $niceHotel,
            departureCity: $tashkent,
            istanbulCity: $istanbul,
            destinationCity: $nice,
            destinationCountry: $france,
            destinationResort: $niceStade,
            sultanahmet: $sultanahmet,
            tourDefaults: $tourDefaults,
            mealTypeId: $mealBB->id,
            pricingService: $pricingService,
            allFlights: $allFlights,
            outboundRouteKey: 'TAS-IST',
            returnRouteKey: null, // Nice return flights TBD
        );
        $this->command->info("Created {$niceCount} Istanbul+Nice tours.");

        // ── Generate Istanbul+Baku Tours ──
        $bakuHotels = Hotel::where('resort_id', $bakuBoulevard->id)->where('is_active', true)->get();
        if ($bakuHotels->isEmpty()) {
            $this->command->warn('No Baku hotels found — skipping Istanbul+Baku tours.');
        } else {
            $defaultIstHotel = $istHotels[1]; // Grand Emir
            $bakuCount = 0;
            $startDate = Carbon::parse(self::SEASON_START);
            $endDate = Carbon::parse(self::SEASON_END);

            while ($startDate->lte($endDate)) {
                foreach ($bakuHotels as $bakuHotel) {
                    $exists = Tour::where('date_from', $startDate->format('Y-m-d'))
                        ->where('hotel_id', $bakuHotel->id)
                        ->whereHas('stays', fn ($q) => $q->where('hotel_id', $defaultIstHotel->id))
                        ->exists();
                    if ($exists) { continue; }

                    $tour = Tour::create(array_merge($tourDefaults, [
                        'date_from' => $startDate->format('Y-m-d'),
                        'date_to' => $startDate->copy()->addDays(self::TOTAL_NIGHTS)->format('Y-m-d'),
                        'departure_city_id' => $tashkent->id,
                        'country_id' => $azerbaijan->id,
                        'hotel_id' => $bakuHotel->id,
                        'resort_id' => $bakuBoulevard->id,
                    ]));

                    TourStay::create([
                        'tour_id' => $tour->id, 'city_id' => $istanbul->id,
                        'resort_id' => $sultanahmet->id, 'hotel_id' => $defaultIstHotel->id,
                        'nights' => self::ISTANBUL_NIGHTS, 'stay_order' => 1, 'meal_type_id' => $mealBB->id,
                    ]);
                    TourStay::create([
                        'tour_id' => $tour->id, 'city_id' => $baku->id,
                        'resort_id' => $bakuBoulevard->id, 'hotel_id' => $bakuHotel->id,
                        'nights' => self::DESTINATION_NIGHTS, 'stay_order' => 2, 'meal_type_id' => $mealBB->id,
                    ]);

                    // Attach flights: TAS→IST outbound, GYD→TAS return (+7 days)
                    $this->attachFlight($tour, $allFlights, 'TAS-IST', $startDate, 'outbound', 1);
                    $returnDate = $startDate->copy()->addDays(self::TOTAL_NIGHTS);
                    $this->attachFlight($tour, $allFlights, 'GYD-TAS', $returnDate, 'return', 2);

                    $pricingService->recalculate($tour);
                    $bakuCount++;
                }
                $startDate->addWeek();
            }
            $this->command->info("Created {$bakuCount} Istanbul+Baku tours.");
        }

        cache()->forget('tour_filter_options');
    }

    private function generateRoute(
        array $istanbulHotels,
        Hotel $destinationHotel,
        City $departureCity,
        City $istanbulCity,
        City $destinationCity,
        Country $destinationCountry,
        Resort $destinationResort,
        Resort $sultanahmet,
        array $tourDefaults,
        int $mealTypeId,
        TourPricingService $pricingService,
        array $allFlights = [],
        ?string $outboundRouteKey = null,
        ?string $returnRouteKey = null,
    ): int {
        $start = Carbon::parse(self::SEASON_START);
        $end = Carbon::parse(self::SEASON_END);
        $count = 0;

        while ($start->lte($end)) {
            foreach ($istanbulHotels as $istHotel) {
                $exists = Tour::where('date_from', $start->format('Y-m-d'))
                    ->where('hotel_id', $destinationHotel->id)
                    ->whereHas('stays', fn ($q) => $q->where('hotel_id', $istHotel->id))
                    ->exists();
                if ($exists) { continue; }

                $tour = Tour::create(array_merge($tourDefaults, [
                    'date_from' => $start->format('Y-m-d'),
                    'date_to' => $start->copy()->addDays(self::TOTAL_NIGHTS)->format('Y-m-d'),
                    'departure_city_id' => $departureCity->id,
                    'country_id' => $destinationCountry->id,
                    'hotel_id' => $destinationHotel->id,
                    'resort_id' => $destinationResort->id,
                ]));

                TourStay::create([
                    'tour_id' => $tour->id, 'city_id' => $istanbulCity->id,
                    'resort_id' => $istHotel->resort_id, 'hotel_id' => $istHotel->id,
                    'nights' => self::ISTANBUL_NIGHTS, 'stay_order' => 1, 'meal_type_id' => $mealTypeId,
                ]);
                TourStay::create([
                    'tour_id' => $tour->id, 'city_id' => $destinationCity->id,
                    'resort_id' => $destinationResort->id, 'hotel_id' => $destinationHotel->id,
                    'nights' => self::DESTINATION_NIGHTS, 'stay_order' => 2, 'meal_type_id' => $mealTypeId,
                ]);

                // Attach flights if available
                if ($outboundRouteKey) {
                    $this->attachFlight($tour, $allFlights, $outboundRouteKey, $start, 'outbound', 1);
                }
                if ($returnRouteKey) {
                    $returnDate = $start->copy()->addDays(self::TOTAL_NIGHTS);
                    $this->attachFlight($tour, $allFlights, $returnRouteKey, $returnDate, 'return', 2);
                }

                $pricingService->recalculate($tour);
                $count++;
            }
            $start->addWeek();
        }

        return $count;
    }

    private function attachFlight(Tour $tour, array $allFlights, string $routeKey, Carbon $date, string $direction, int $legOrder): void
    {
        $key = $routeKey . '-' . $date->format('Y-m-d');
        $flight = $allFlights[$key] ?? null;

        if ($flight && !$tour->flights()->where('flight_id', $flight->id)->exists()) {
            $tour->flights()->attach($flight->id, [
                'direction' => $direction,
                'leg_order' => $legOrder,
            ]);
        }
    }
}
