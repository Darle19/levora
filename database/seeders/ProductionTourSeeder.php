<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\MealType;
use App\Models\ProgramType;
use App\Models\Resort;
use App\Models\Tour;
use App\Models\TourStay;
use App\Models\TourType;
use App\Models\TransportType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds real tour data for production: Istanbul hotels + Istanbul-Nice / Istanbul-Baku tours.
 * Safe to run multiple times — uses firstOrCreate everywhere.
 */
class ProductionTourSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ensure reference data exists ──
        $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]);
        $mealBB = MealType::firstOrCreate(['code' => 'BB'], ['name_en' => 'Bed & Breakfast', 'name_ru' => 'Завтрак', 'name_uz' => 'Nonushta', 'is_active' => true]);
        $transportAir = TransportType::firstOrCreate(['name_en' => 'Air'], ['name_ru' => 'Авиа', 'name_uz' => 'Avia', 'is_active' => true]);
        $tourType = TourType::firstOrCreate(['name_en' => 'Combined'], ['name_ru' => 'Комбинированный', 'name_uz' => 'Kombinatsiyalangan', 'is_active' => true]);
        $programType = ProgramType::firstOrCreate(['name_en' => 'Standard'], ['name_ru' => 'Стандарт', 'name_uz' => 'Standart', 'is_active' => true]);
        $star3 = HotelCategory::firstOrCreate(['stars' => 3], ['name_en' => '3 Star', 'name_ru' => '3 звезды', 'name_uz' => '3 yulduz', 'is_active' => true]);
        $star4 = HotelCategory::firstOrCreate(['stars' => 4], ['name_en' => '4 Star', 'name_ru' => '4 звезды', 'name_uz' => '4 yulduz', 'is_active' => true]);

        // ── Countries ──
        $turkey = Country::firstOrCreate(['name_en' => 'Turkey'], ['name_ru' => 'Турция', 'name_uz' => 'Turkiya', 'is_active' => true, 'order' => 1]);
        $france = Country::firstOrCreate(['name_en' => 'France'], ['name_ru' => 'Франция', 'name_uz' => 'Frantsiya', 'is_active' => true, 'order' => 2]);
        $azerbaijan = Country::firstOrCreate(['name_en' => 'Azerbaijan'], ['name_ru' => 'Азербайджан', 'name_uz' => 'Ozarbayjon', 'is_active' => true, 'order' => 3]);

        // ── Cities ──
        $tashkent = City::firstOrCreate(['name_en' => 'Tashkent'], ['name_ru' => 'Ташкент', 'name_uz' => 'Toshkent', 'country_id' => null, 'is_active' => true, 'order' => 1]);
        $istanbul = City::firstOrCreate(['name_en' => 'Istanbul'], ['name_ru' => 'Стамбул', 'name_uz' => 'Istanbul', 'country_id' => $turkey->id, 'is_active' => true, 'order' => 2]);
        $nice = City::firstOrCreate(['name_en' => 'Nice'], ['name_ru' => 'Ницца', 'name_uz' => 'Nitsa', 'country_id' => $france->id, 'is_active' => true, 'order' => 3]);
        $baku = City::firstOrCreate(['name_en' => 'Baku'], ['name_ru' => 'Баку', 'name_uz' => 'Boku', 'country_id' => $azerbaijan->id, 'is_active' => true, 'order' => 4]);

        // ── Resorts ──
        $sultanahmet = Resort::firstOrCreate(['name_en' => 'Sultanahmet'], ['city_id' => $istanbul->id, 'country_id' => $turkey->id, 'is_active' => true, 'order' => 1]);
        $fatih = Resort::firstOrCreate(['name_en' => 'Fatih'], ['city_id' => $istanbul->id, 'country_id' => $turkey->id, 'is_active' => true, 'order' => 2]);
        $niceStade = Resort::firstOrCreate(['name_en' => 'Nice Stade'], ['city_id' => $nice->id, 'country_id' => $france->id, 'is_active' => true, 'order' => 1]);
        $bakuBoulevard = Resort::firstOrCreate(['name_en' => 'Baku Boulevard'], ['city_id' => $baku->id, 'country_id' => $azerbaijan->id, 'is_active' => true, 'order' => 1]);

        // ── Istanbul Hotels ──
        $istanbulHotels = [
            ['name' => 'Grand Liza Hotel', 'price' => 45, 'resort' => $fatih],
            ['name' => 'Grand Emir Hotel', 'price' => 50, 'resort' => $fatih],
            ['name' => 'All Seasons Hotel Istanbul', 'price' => 55, 'resort' => $fatih],
            ['name' => 'New Emin Hotel', 'price' => 61, 'resort' => $sultanahmet],
            ['name' => 'River Hotel', 'price' => 62, 'resort' => $fatih],
            ['name' => 'Grand Washington Hotel', 'price' => 75, 'resort' => $sultanahmet],
            ['name' => 'Sorisso Hotel', 'price' => 75, 'resort' => $sultanahmet],
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
                    'price_per_person' => $h['price'], 'currency_id' => $usd->id,
                ]
            );
            $hotel->update(['price_per_person' => $h['price']]);
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

        // ── Generate Istanbul+Nice Tours (Apr-Jun, weekly) ──
        $startDate = Carbon::parse('2026-04-01');
        $endDate = Carbon::parse('2026-06-30');
        $niceCount = 0;

        while ($startDate->lte($endDate)) {
            foreach ($istHotels as $istHotel) {
                $exists = Tour::where('date_from', $startDate->format('Y-m-d'))
                    ->where('hotel_id', $niceHotel->id)
                    ->whereHas('stays', fn ($q) => $q->where('hotel_id', $istHotel->id))
                    ->exists();
                if ($exists) { continue; }

                $price = ($istHotel->price_per_person * 2) + ($niceHotel->price_per_person * 4) + 400 + 40;

                $tour = Tour::create([
                    'date_from' => $startDate->format('Y-m-d'),
                    'date_to' => $startDate->copy()->addDays(7)->format('Y-m-d'),
                    'nights' => 7, 'adults' => 2, 'children' => 0, 'price' => $price,
                    'departure_city_id' => $tashkent->id, 'country_id' => $france->id,
                    'hotel_id' => $niceHotel->id, 'resort_id' => $niceStade->id,
                    'meal_type_id' => $mealBB->id, 'transport_type_id' => $transportAir->id,
                    'currency_id' => $usd->id, 'tour_type_id' => $tourType->id,
                    'program_type_id' => $programType->id,
                    'is_available' => true, 'is_hot' => false,
                ]);

                TourStay::create([
                    'tour_id' => $tour->id, 'city_id' => $istanbul->id,
                    'resort_id' => $istHotel->resort_id, 'hotel_id' => $istHotel->id,
                    'nights' => 2, 'stay_order' => 1, 'meal_type_id' => $mealBB->id,
                ]);
                TourStay::create([
                    'tour_id' => $tour->id, 'city_id' => $nice->id,
                    'resort_id' => $niceStade->id, 'hotel_id' => $niceHotel->id,
                    'nights' => 4, 'stay_order' => 2, 'meal_type_id' => $mealBB->id,
                ]);
                $niceCount++;
            }
            $startDate->addWeek();
        }
        $this->command->info("Created {$niceCount} Istanbul+Nice tours.");

        // ── Generate Istanbul+Baku Tours ──
        $bakuHotels = Hotel::where('resort_id', $bakuBoulevard->id)->where('is_active', true)->get();
        if ($bakuHotels->isEmpty()) {
            $this->command->warn('No Baku hotels found — skipping Istanbul+Baku tours.');
            return;
        }

        $startDate = Carbon::parse('2026-04-01');
        $defaultIstHotel = $istHotels[1]; // Grand Emir
        $bakuCount = 0;

        while ($startDate->lte($endDate)) {
            foreach ($bakuHotels as $bakuHotel) {
                $exists = Tour::where('date_from', $startDate->format('Y-m-d'))
                    ->where('hotel_id', $bakuHotel->id)
                    ->whereHas('stays', fn ($q) => $q->where('hotel_id', $defaultIstHotel->id))
                    ->exists();
                if ($exists) { continue; }

                $price = ($defaultIstHotel->price_per_person * 2) + ($bakuHotel->price_per_person * 4) + 400 + 40;

                $tour = Tour::create([
                    'date_from' => $startDate->format('Y-m-d'),
                    'date_to' => $startDate->copy()->addDays(7)->format('Y-m-d'),
                    'nights' => 7, 'adults' => 2, 'children' => 0, 'price' => $price,
                    'departure_city_id' => $tashkent->id, 'country_id' => $azerbaijan->id,
                    'hotel_id' => $bakuHotel->id, 'resort_id' => $bakuBoulevard->id,
                    'meal_type_id' => $mealBB->id, 'transport_type_id' => $transportAir->id,
                    'currency_id' => $usd->id, 'tour_type_id' => $tourType->id,
                    'program_type_id' => $programType->id,
                    'is_available' => true, 'is_hot' => false,
                ]);

                TourStay::create([
                    'tour_id' => $tour->id, 'city_id' => $istanbul->id,
                    'resort_id' => $sultanahmet->id, 'hotel_id' => $defaultIstHotel->id,
                    'nights' => 2, 'stay_order' => 1, 'meal_type_id' => $mealBB->id,
                ]);
                TourStay::create([
                    'tour_id' => $tour->id, 'city_id' => $baku->id,
                    'resort_id' => $bakuBoulevard->id, 'hotel_id' => $bakuHotel->id,
                    'nights' => 4, 'stay_order' => 2, 'meal_type_id' => $mealBB->id,
                ]);
                $bakuCount++;
            }
            $startDate->addWeek();
        }
        $this->command->info("Created {$bakuCount} Istanbul+Baku tours.");
        cache()->forget('tour_filter_options');
    }
}
