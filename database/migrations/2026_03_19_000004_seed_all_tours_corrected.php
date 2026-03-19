<?php

use App\Models\Flight;
use App\Models\Tour;
use App\Models\TourAmadeusSegment;
use App\Models\TourPrice;
use App\Models\TourStay;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates correct tour lineup:
 * 1. Istanbul+Baku (Centrum Air block, all 4 legs)
 * 2. Nice-only (Turkish Airlines block, 7 nights)
 * 3. Paris-only (Turkish Airlines block, 7 nights)
 *
 * Also removes wrong Bali tours if they were created by previous migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Remove wrong Bali tours from previous migration
        $hardRockId = DB::table('hotels')->where('name', 'Hard Rock Hotel Bali')->value('id');
        if ($hardRockId) {
            $baliTourIds = DB::table('tours')->where('hotel_id', $hardRockId)->where('country_id', DB::table('countries')->where('name_en', 'Indonesia')->value('id'))->pluck('id');
            if ($baliTourIds->isNotEmpty()) {
                DB::table('tour_prices')->whereIn('tour_id', $baliTourIds)->delete();
                DB::table('tour_stays')->whereIn('tour_id', $baliTourIds)->delete();
                DB::table('tour_amadeus_segments')->whereIn('tour_id', $baliTourIds)->delete();
                DB::table('tour_flight')->whereIn('tour_id', $baliTourIds)->delete();
                DB::table('tours')->whereIn('id', $baliTourIds)->delete();
            }
        }

        $centrumId = DB::table('airlines')->where('code', 'C2')->value('id');
        $tkId = DB::table('airlines')->where('code', 'TK')->value('id');
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        $tasId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istId = DB::table('airports')->where('code', 'IST')->value('id');
        $gydId = DB::table('airports')->where('code', 'GYD')->value('id');
        $nceId = DB::table('airports')->where('code', 'NCE')->value('id');
        $cdgId = DB::table('airports')->where('code', 'CDG')->value('id');
        $bbMealId = DB::table('meal_types')->first()?->id;
        $combinedTypeId = DB::table('tour_types')->where('name_en', 'Combined')->value('id') ?? DB::table('tour_types')->first()?->id;
        $beachTypeId = DB::table('tour_types')->where('name_en', 'Beach Holiday')->value('id') ?? $combinedTypeId;
        $standardId = DB::table('program_types')->first()?->id;
        $airplaneId = DB::table('transport_types')->first()?->id;
        $tashkentCityId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $doubleRoomId = DB::table('room_types')->where('name_en', 'Double')->value('id');
        $azerbaijanId = DB::table('countries')->where('name_en', 'Azerbaijan')->value('id');
        $franceId = DB::table('countries')->where('name_en', 'France')->value('id');
        $bakuCityId = DB::table('cities')->where('name_en', 'Baku')->value('id');
        $istanbulCityId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $niceCityId = DB::table('cities')->where('name_en', 'Nice')->value('id');
        $parisCityId = DB::table('cities')->where('name_en', 'Paris')->value('id');
        $sultanahmetId = DB::table('resorts')->where('name_en', 'Sultanahmet')->value('id');
        $fountainSqId = DB::table('resorts')->where('name_en', 'Fountain Square')->value('id');
        $niceResortId = DB::table('resorts')->where('name_en', 'Nice Stade')->value('id');
        $montmartreId = DB::table('resorts')->where('name_en', 'Montmartre')->value('id');
        $grandEmirId = DB::table('hotels')->where('name', 'Grand Emir Hotel')->value('id');
        $fairmontBakuId = DB::table('hotels')->where('name', 'Fairmont Baku Flame Towers')->value('id');
        $bbNiceId = DB::table('hotels')->where('name', 'B&B HOTEL Nice Stade Riviera 3 étoiles')->value('id');
        $timhotelId = DB::table('hotels')->where('name', 'Timhotel Montmartre')->value('id');
        $eurId = DB::table('currencies')->where('code', 'EUR')->value('id');

        if (! $centrumId || ! $tkId || ! $tasId || ! $istId || ! $usdId) {
            throw new \RuntimeException('Missing critical reference data.');
        }

        $monday = Carbon::now()->next(Carbon::MONDAY);

        for ($i = 0; $i < 12; $i++) {
            $dep = $monday->copy()->addWeeks($i);
            $ret = $dep->copy()->addDays(6);

            // ===== TOUR 1: Istanbul+Baku (Centrum Air, all block) =====
            if ($gydId && $azerbaijanId && $fountainSqId) {
                $tasIst = Flight::where('flight_number', 'C2 501')->whereDate('departure_date', $dep->format('Y-m-d'))->first();
                $istTas = Flight::where('flight_number', 'C2 502')->whereDate('departure_date', $ret->format('Y-m-d'))->first();

                if (! $tasIst) {
                    $tasIst = Flight::create(['airline_id' => $centrumId, 'from_airport_id' => $tasId, 'to_airport_id' => $istId, 'currency_id' => $usdId, 'flight_number' => 'C2 501', 'departure_date' => $dep->format('Y-m-d'), 'departure_time' => '06:00', 'arrival_date' => $dep->format('Y-m-d'), 'arrival_time' => '10:00', 'price_adult' => 200, 'price_child' => 160, 'price_infant' => 40, 'hard_block_price' => 180, 'available_seats' => 30, 'class_type' => 'economy', 'is_active' => true]);
                }
                if (! $istTas) {
                    $istTas = Flight::create(['airline_id' => $centrumId, 'from_airport_id' => $istId, 'to_airport_id' => $tasId, 'currency_id' => $usdId, 'flight_number' => 'C2 502', 'departure_date' => $ret->format('Y-m-d'), 'departure_time' => '22:00', 'arrival_date' => $ret->copy()->addDay()->format('Y-m-d'), 'arrival_time' => '04:00', 'price_adult' => 200, 'price_child' => 160, 'price_infant' => 40, 'hard_block_price' => 180, 'available_seats' => 30, 'class_type' => 'economy', 'is_active' => true]);
                }

                $istGyd = Flight::create(['airline_id' => $centrumId, 'from_airport_id' => $istId, 'to_airport_id' => $gydId, 'currency_id' => $usdId, 'flight_number' => 'C2 601', 'departure_date' => $dep->format('Y-m-d'), 'departure_time' => '14:00', 'arrival_date' => $dep->format('Y-m-d'), 'arrival_time' => '17:00', 'price_adult' => 150, 'price_child' => 120, 'price_infant' => 30, 'hard_block_price' => 130, 'available_seats' => 30, 'class_type' => 'economy', 'is_active' => true]);
                $gydIst = Flight::create(['airline_id' => $centrumId, 'from_airport_id' => $gydId, 'to_airport_id' => $istId, 'currency_id' => $usdId, 'flight_number' => 'C2 602', 'departure_date' => $ret->format('Y-m-d'), 'departure_time' => '08:00', 'arrival_date' => $ret->format('Y-m-d'), 'arrival_time' => '11:00', 'price_adult' => 150, 'price_child' => 120, 'price_infant' => 30, 'hard_block_price' => 130, 'available_seats' => 30, 'class_type' => 'economy', 'is_active' => true]);

                $tour = Tour::create(['tour_type_id' => $combinedTypeId, 'program_type_id' => $standardId, 'country_id' => $azerbaijanId, 'resort_id' => $fountainSqId, 'hotel_id' => $fairmontBakuId, 'transport_type_id' => $airplaneId, 'departure_city_id' => $tashkentCityId, 'meal_type_id' => $bbMealId, 'currency_id' => $usdId, 'nights' => 7, 'date_from' => $dep->format('Y-m-d'), 'date_to' => $ret->format('Y-m-d'), 'adults' => 2, 'children' => 0, 'price' => 850.00, 'is_available' => true, 'is_hot' => false, 'instant_confirmation' => true, 'no_stop_sale' => false]);

                $tour->flights()->attach($tasIst->id, ['direction' => 'outbound', 'leg_order' => 1]);
                $tour->flights()->attach($istGyd->id, ['direction' => 'outbound', 'leg_order' => 2]);
                $tour->flights()->attach($gydIst->id, ['direction' => 'return', 'leg_order' => 3]);
                $tour->flights()->attach($istTas->id, ['direction' => 'return', 'leg_order' => 4]);

                TourStay::create(['tour_id' => $tour->id, 'stay_order' => 1, 'city_id' => $istanbulCityId, 'resort_id' => $sultanahmetId, 'hotel_id' => $grandEmirId, 'nights' => 2, 'meal_type_id' => $bbMealId, 'price_per_person' => 43.00, 'currency_id' => $eurId]);
                TourStay::create(['tour_id' => $tour->id, 'stay_order' => 2, 'city_id' => $bakuCityId, 'resort_id' => $fountainSqId, 'hotel_id' => $fairmontBakuId, 'nights' => 5, 'meal_type_id' => $bbMealId, 'price_per_person' => 130.00, 'currency_id' => $usdId]);
                TourPrice::create(['tour_id' => $tour->id, 'room_type_id' => $doubleRoomId, 'price_adult' => 850, 'price_child' => 680, 'price_infant' => 120, 'availability' => 15, 'currency_id' => $usdId, 'is_active' => true]);
            }

            // ===== TOUR 2: Nice-only (Turkish Airlines, all block) =====
            if ($nceId && $bbNiceId && $niceResortId) {
                $tkTasIst = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $tasId, 'to_airport_id' => $istId, 'currency_id' => $usdId, 'flight_number' => 'TK 372', 'departure_date' => $dep->format('Y-m-d'), 'departure_time' => '07:00', 'arrival_date' => $dep->format('Y-m-d'), 'arrival_time' => '11:00', 'price_adult' => 250, 'price_child' => 200, 'price_infant' => 50, 'hard_block_price' => 220, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);
                $tkIstNce = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $istId, 'to_airport_id' => $nceId, 'currency_id' => $usdId, 'flight_number' => 'TK 1803', 'departure_date' => $dep->format('Y-m-d'), 'departure_time' => '14:30', 'arrival_date' => $dep->format('Y-m-d'), 'arrival_time' => '17:00', 'price_adult' => 180, 'price_child' => 144, 'price_infant' => 36, 'hard_block_price' => 160, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);
                $tkNceIst = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $nceId, 'to_airport_id' => $istId, 'currency_id' => $usdId, 'flight_number' => 'TK 1804', 'departure_date' => $ret->format('Y-m-d'), 'departure_time' => '09:00', 'arrival_date' => $ret->format('Y-m-d'), 'arrival_time' => '13:00', 'price_adult' => 180, 'price_child' => 144, 'price_infant' => 36, 'hard_block_price' => 160, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);
                $tkIstTas = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $istId, 'to_airport_id' => $tasId, 'currency_id' => $usdId, 'flight_number' => 'TK 373', 'departure_date' => $ret->format('Y-m-d'), 'departure_time' => '16:00', 'arrival_date' => $ret->copy()->addDay()->format('Y-m-d'), 'arrival_time' => '00:30', 'price_adult' => 250, 'price_child' => 200, 'price_infant' => 50, 'hard_block_price' => 220, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);

                $tour = Tour::create(['tour_type_id' => $beachTypeId, 'program_type_id' => $standardId, 'country_id' => $franceId, 'resort_id' => $niceResortId, 'hotel_id' => $bbNiceId, 'transport_type_id' => $airplaneId, 'departure_city_id' => $tashkentCityId, 'meal_type_id' => $bbMealId, 'currency_id' => $usdId, 'nights' => 7, 'date_from' => $dep->format('Y-m-d'), 'date_to' => $ret->format('Y-m-d'), 'adults' => 2, 'children' => 0, 'price' => 1630, 'is_available' => true, 'is_hot' => false, 'instant_confirmation' => true, 'no_stop_sale' => false]);

                $tour->flights()->attach($tkTasIst->id, ['direction' => 'outbound', 'leg_order' => 1]);
                $tour->flights()->attach($tkIstNce->id, ['direction' => 'outbound', 'leg_order' => 2]);
                $tour->flights()->attach($tkNceIst->id, ['direction' => 'return', 'leg_order' => 3]);
                $tour->flights()->attach($tkIstTas->id, ['direction' => 'return', 'leg_order' => 4]);

                TourStay::create(['tour_id' => $tour->id, 'stay_order' => 1, 'city_id' => $niceCityId, 'resort_id' => $niceResortId, 'hotel_id' => $bbNiceId, 'nights' => 7, 'meal_type_id' => $bbMealId, 'price_per_person' => 110, 'currency_id' => $usdId]);
                TourPrice::create(['tour_id' => $tour->id, 'room_type_id' => $doubleRoomId, 'price_adult' => 1630, 'price_child' => 1300, 'price_infant' => 200, 'availability' => 10, 'currency_id' => $usdId, 'is_active' => true]);
            }

            // ===== TOUR 3: Paris-only (Turkish Airlines, all block) =====
            if ($cdgId && $timhotelId && $montmartreId) {
                $tkTasIst2 = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $tasId, 'to_airport_id' => $istId, 'currency_id' => $usdId, 'flight_number' => 'TK 372', 'departure_date' => $dep->format('Y-m-d'), 'departure_time' => '07:00', 'arrival_date' => $dep->format('Y-m-d'), 'arrival_time' => '11:00', 'price_adult' => 250, 'price_child' => 200, 'price_infant' => 50, 'hard_block_price' => 220, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);
                $tkIstCdg = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $istId, 'to_airport_id' => $cdgId, 'currency_id' => $usdId, 'flight_number' => 'TK 1823', 'departure_date' => $dep->format('Y-m-d'), 'departure_time' => '13:00', 'arrival_date' => $dep->format('Y-m-d'), 'arrival_time' => '16:00', 'price_adult' => 200, 'price_child' => 160, 'price_infant' => 40, 'hard_block_price' => 180, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);
                $tkCdgIst = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $cdgId, 'to_airport_id' => $istId, 'currency_id' => $usdId, 'flight_number' => 'TK 1824', 'departure_date' => $ret->format('Y-m-d'), 'departure_time' => '08:00', 'arrival_date' => $ret->format('Y-m-d'), 'arrival_time' => '12:30', 'price_adult' => 200, 'price_child' => 160, 'price_infant' => 40, 'hard_block_price' => 180, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);
                $tkIstTas2 = Flight::create(['airline_id' => $tkId, 'from_airport_id' => $istId, 'to_airport_id' => $tasId, 'currency_id' => $usdId, 'flight_number' => 'TK 373', 'departure_date' => $ret->format('Y-m-d'), 'departure_time' => '16:00', 'arrival_date' => $ret->copy()->addDay()->format('Y-m-d'), 'arrival_time' => '00:30', 'price_adult' => 250, 'price_child' => 200, 'price_infant' => 50, 'hard_block_price' => 220, 'available_seats' => 20, 'class_type' => 'economy', 'is_active' => true]);

                $tour = Tour::create(['tour_type_id' => $beachTypeId, 'program_type_id' => $standardId, 'country_id' => $franceId, 'resort_id' => $montmartreId, 'hotel_id' => $timhotelId, 'transport_type_id' => $airplaneId, 'departure_city_id' => $tashkentCityId, 'meal_type_id' => $bbMealId, 'currency_id' => $usdId, 'nights' => 7, 'date_from' => $dep->format('Y-m-d'), 'date_to' => $ret->format('Y-m-d'), 'adults' => 2, 'children' => 0, 'price' => 1460, 'is_available' => true, 'is_hot' => false, 'instant_confirmation' => true, 'no_stop_sale' => false]);

                $tour->flights()->attach($tkTasIst2->id, ['direction' => 'outbound', 'leg_order' => 1]);
                $tour->flights()->attach($tkIstCdg->id, ['direction' => 'outbound', 'leg_order' => 2]);
                $tour->flights()->attach($tkCdgIst->id, ['direction' => 'return', 'leg_order' => 3]);
                $tour->flights()->attach($tkIstTas2->id, ['direction' => 'return', 'leg_order' => 4]);

                TourStay::create(['tour_id' => $tour->id, 'stay_order' => 1, 'city_id' => $parisCityId, 'resort_id' => $montmartreId, 'hotel_id' => $timhotelId, 'nights' => 7, 'meal_type_id' => $bbMealId, 'price_per_person' => 80, 'currency_id' => $usdId]);
                TourPrice::create(['tour_id' => $tour->id, 'room_type_id' => $doubleRoomId, 'price_adult' => 1460, 'price_child' => 1168, 'price_infant' => 200, 'availability' => 10, 'currency_id' => $usdId, 'is_active' => true]);
            }
        }
    }

    public function down(): void
    {
        // Remove Istanbul+Baku tours
        DB::table('flights')->where('flight_number', 'C2 601')->delete();
        DB::table('flights')->where('flight_number', 'C2 602')->delete();

        $fairmontId = DB::table('hotels')->where('name', 'Fairmont Baku Flame Towers')->value('id');
        if ($fairmontId) {
            $ids = DB::table('tours')->where('hotel_id', $fairmontId)->pluck('id');
            DB::table('tour_prices')->whereIn('tour_id', $ids)->delete();
            DB::table('tour_stays')->whereIn('tour_id', $ids)->delete();
            DB::table('tour_flight')->whereIn('tour_id', $ids)->delete();
            DB::table('tours')->whereIn('id', $ids)->delete();
        }

        // Remove Nice-only + Paris-only tours (TK flights)
        DB::table('flights')->where('flight_number', 'TK 1803')->delete();
        DB::table('flights')->where('flight_number', 'TK 1804')->delete();
        DB::table('flights')->where('flight_number', 'TK 1823')->delete();
        DB::table('flights')->where('flight_number', 'TK 1824')->delete();

        $bbNiceId = DB::table('hotels')->where('name', 'B&B HOTEL Nice Stade Riviera 3 étoiles')->value('id');
        $timhotelId = DB::table('hotels')->where('name', 'Timhotel Montmartre')->value('id');

        foreach ([$bbNiceId, $timhotelId] as $hotelId) {
            if (! $hotelId) continue;
            $ids = DB::table('tours')->where('hotel_id', $hotelId)->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('tour_amadeus_segments')->whereColumn('tour_amadeus_segments.tour_id', 'tours.id');
            })->pluck('id');
            DB::table('tour_prices')->whereIn('tour_id', $ids)->delete();
            DB::table('tour_stays')->whereIn('tour_id', $ids)->delete();
            DB::table('tour_flight')->whereIn('tour_id', $ids)->delete();
            DB::table('tours')->whereIn('id', $ids)->delete();
        }

        // Remove duplicate TK 372/373 flights (keep originals)
        DB::table('flights')->where('flight_number', 'TK 372')->delete();
        DB::table('flights')->where('flight_number', 'TK 373')->delete();
    }
};
