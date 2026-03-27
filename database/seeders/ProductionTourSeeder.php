<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Tour;
use App\Models\TourStay;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds real tour data for production: Istanbul hotels + Istanbul-Nice / Istanbul-Baku tours.
 * Safe to run multiple times — skips existing data.
 */
class ProductionTourSeeder extends Seeder
{
    public function run(): void
    {
        // Reference IDs (must exist from base seeders)
        $tashkent = 1;        // departure city
        $istanbul = 2;        // city
        $nice = 10;           // city
        $baku = 9;            // city
        $turkeyCountry = 2;
        $franceCountry = 6;
        $azerbaijanCountry = 9;
        $sultanahmet = 5;     // resort in Istanbul
        $fatih = 6;           // resort in Istanbul
        $niceStade = 31;      // resort in Nice
        $bakuBoulevard = 30;  // resort in Baku
        $mealBB = 1;
        $transportAir = 1;
        $currencyUSD = 1;
        $star3 = 3;           // hotel_category_id for 3 stars
        $star4 = 2;           // hotel_category_id for 4 stars
        $tourType = 1;
        $programType = 1;

        // ── Istanbul Hotels ──
        $istanbulHotels = [
            ['name' => 'Grand Liza Hotel', 'price' => 45, 'resort_id' => $fatih, 'cat' => $star3],
            ['name' => 'Grand Emir Hotel', 'price' => 50, 'resort_id' => $fatih, 'cat' => $star3],
            ['name' => 'All Seasons Hotel Istanbul', 'price' => 55, 'resort_id' => $fatih, 'cat' => $star3],
            ['name' => 'New Emin Hotel', 'price' => 61, 'resort_id' => $sultanahmet, 'cat' => $star3],
            ['name' => 'River Hotel', 'price' => 62, 'resort_id' => $fatih, 'cat' => $star3],
            ['name' => 'Grand Washington Hotel', 'price' => 75, 'resort_id' => $sultanahmet, 'cat' => $star3],
            ['name' => 'Sorisso Hotel', 'price' => 75, 'resort_id' => $sultanahmet, 'cat' => $star3],
        ];

        $istHotelIds = [];
        foreach ($istanbulHotels as $h) {
            $hotel = Hotel::firstOrCreate(
                ['name' => $h['name']],
                [
                    'name_en' => $h['name'],
                    'name_ru' => $h['name'],
                    'name_uz' => $h['name'],
                    'description' => 'Hotel in Istanbul, Turkey',
                    'address' => 'Istanbul, Turkey',
                    'resort_id' => $h['resort_id'],
                    'hotel_category_id' => $h['cat'],
                    'rating' => 3.5,
                    'is_active' => true,
                    'price_per_person' => $h['price'],
                    'currency_id' => $currencyUSD,
                ]
            );
            $hotel->update(['price_per_person' => $h['price']]);
            $istHotelIds[$h['name']] = $hotel->id;
        }

        // Nice hotel
        $niceHotel = Hotel::firstOrCreate(
            ['name' => 'B&B HOTEL Nice Stade Riviera 3 étoiles'],
            [
                'name_en' => 'B&B HOTEL Nice Stade Riviera',
                'name_ru' => 'B&B HOTEL Nice Stade Riviera',
                'name_uz' => 'B&B HOTEL Nice Stade Riviera',
                'description' => 'Hotel in Nice, France',
                'address' => 'Nice, France',
                'resort_id' => $niceStade,
                'hotel_category_id' => $star3,
                'rating' => 3.0,
                'is_active' => true,
                'price_per_person' => 110,
                'currency_id' => $currencyUSD,
            ]
        );

        // ── Tours: Istanbul + Nice ──
        // Generate tours for April-June 2026, weekly departures
        $startDate = Carbon::parse('2026-04-01');
        $endDate = Carbon::parse('2026-06-30');
        $tourCount = 0;

        while ($startDate->lte($endDate)) {
            foreach ($istHotelIds as $hotelName => $istHotelId) {
                $istHotel = Hotel::find($istHotelId);

                // Skip if tour already exists for this date + hotel
                $exists = Tour::where('date_from', $startDate->format('Y-m-d'))
                    ->where('hotel_id', $niceHotel->id)
                    ->whereHas('stays', fn ($q) => $q->where('hotel_id', $istHotelId))
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Price: (istanbul_hotel + nice_hotel) * 7 nights equivalent + markup
                $basePrice = ($istHotel->price_per_person * 2) + ($niceHotel->price_per_person * 4);
                $flightCost = 400; // Centrum Air round trip
                $transferCost = 40;
                $totalPrice = $basePrice + $flightCost + $transferCost;

                $tour = Tour::create([
                    'date_from' => $startDate->format('Y-m-d'),
                    'date_to' => $startDate->copy()->addDays(7)->format('Y-m-d'),
                    'nights' => 7,
                    'adults' => 2,
                    'children' => 0,
                    'price' => $totalPrice,
                    'departure_city_id' => $tashkent,
                    'country_id' => $franceCountry,
                    'hotel_id' => $niceHotel->id,
                    'resort_id' => $niceStade,
                    'meal_type_id' => $mealBB,
                    'transport_type_id' => $transportAir,
                    'currency_id' => $currencyUSD,
                    'tour_type_id' => $tourType,
                    'program_type_id' => $programType,
                    'is_available' => true,
                    'is_hot' => false,
                ]);

                // Stay 1: Istanbul 2 nights
                TourStay::create([
                    'tour_id' => $tour->id,
                    'city_id' => $istanbul,
                    'resort_id' => $istHotel->resort_id,
                    'hotel_id' => $istHotelId,
                    'nights' => 2,
                    'stay_order' => 1,
                    'meal_type_id' => $mealBB,
                ]);

                // Stay 2: Nice 4 nights
                TourStay::create([
                    'tour_id' => $tour->id,
                    'city_id' => $nice,
                    'resort_id' => $niceStade,
                    'hotel_id' => $niceHotel->id,
                    'nights' => 4,
                    'stay_order' => 2,
                    'meal_type_id' => $mealBB,
                ]);

                $tourCount++;
            }

            $startDate->addWeek();
        }

        $this->command->info("Created {$tourCount} Istanbul+Nice tours.");

        // ── Tours: Istanbul + Baku (similar structure) ──
        // Use same Istanbul hotels, Baku has its own hotels from base seeders
        $bakuHotels = Hotel::where('resort_id', $bakuBoulevard)->where('is_active', true)->get();
        if ($bakuHotels->isEmpty()) {
            $this->command->warn('No Baku hotels found — skipping Istanbul+Baku tours.');
            return;
        }

        $startDate = Carbon::parse('2026-04-01');
        $bakuCount = 0;

        while ($startDate->lte($endDate)) {
            foreach ($bakuHotels as $bakuHotel) {
                $defaultIstHotelId = $istHotelIds['Grand Emir Hotel'];
                $istHotel = Hotel::find($defaultIstHotelId);

                $exists = Tour::where('date_from', $startDate->format('Y-m-d'))
                    ->where('hotel_id', $bakuHotel->id)
                    ->whereHas('stays', fn ($q) => $q->where('hotel_id', $defaultIstHotelId))
                    ->exists();

                if ($exists) {
                    continue;
                }

                $basePrice = ($istHotel->price_per_person * 2) + ($bakuHotel->price_per_person * 4);
                $totalPrice = $basePrice + 400 + 40;

                $tour = Tour::create([
                    'date_from' => $startDate->format('Y-m-d'),
                    'date_to' => $startDate->copy()->addDays(7)->format('Y-m-d'),
                    'nights' => 7,
                    'adults' => 2,
                    'children' => 0,
                    'price' => $totalPrice,
                    'departure_city_id' => $tashkent,
                    'country_id' => $azerbaijanCountry,
                    'hotel_id' => $bakuHotel->id,
                    'resort_id' => $bakuBoulevard,
                    'meal_type_id' => $mealBB,
                    'transport_type_id' => $transportAir,
                    'currency_id' => $currencyUSD,
                    'tour_type_id' => $tourType,
                    'program_type_id' => $programType,
                    'is_available' => true,
                    'is_hot' => false,
                ]);

                TourStay::create([
                    'tour_id' => $tour->id,
                    'city_id' => $istanbul,
                    'resort_id' => $sultanahmet,
                    'hotel_id' => $defaultIstHotelId,
                    'nights' => 2,
                    'stay_order' => 1,
                    'meal_type_id' => $mealBB,
                ]);

                TourStay::create([
                    'tour_id' => $tour->id,
                    'city_id' => $baku,
                    'resort_id' => $bakuBoulevard,
                    'hotel_id' => $bakuHotel->id,
                    'nights' => 4,
                    'stay_order' => 2,
                    'meal_type_id' => $mealBB,
                ]);

                $bakuCount++;
            }

            $startDate->addWeek();
        }

        $this->command->info("Created {$bakuCount} Istanbul+Baku tours.");

        // Clear cache so new tours appear in search
        cache()->forget('tour_filter_options');
    }
}
