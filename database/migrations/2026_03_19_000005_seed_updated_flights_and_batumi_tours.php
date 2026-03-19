<?php

use App\Models\Flight;
use App\Models\Tour;
use App\Models\TourPrice;
use App\Models\TourStay;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Updates flight pricing from spreadsheet data and creates Batumi tours.
 *
 * 1. Updates C2 501 (TAS→IST) hard/soft block prices by month
 * 2. Updates C2 601/602 (IST↔GYD) prices by month
 * 3. Creates C2 603 (GYD→TAS) direct return flights
 * 4. Creates C2 701/702 (TAS↔BUS) Batumi round trip flights
 * 5. Creates 9 Batumi beach holiday tours
 */
return new class extends Migration
{
    public function up(): void
    {
        $centrumId = DB::table('airlines')->where('code', 'C2')->value('id');
        $tasId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istId = DB::table('airports')->where('code', 'IST')->value('id');
        $gydId = DB::table('airports')->where('code', 'GYD')->value('id');
        $busId = DB::table('airports')->where('code', 'BUS')->value('id');
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');

        if (! $centrumId || ! $tasId || ! $usdId) {
            throw new \RuntimeException('Missing critical reference data (C2 airline, TAS airport, or USD currency).');
        }

        // === 1. Update C2 501 (TAS→IST) pricing ===
        $flights501 = Flight::where('flight_number', 'C2 501')->get();
        foreach ($flights501 as $f) {
            $m = $f->departure_date->month;
            $data = match (true) {
                $m <= 4 => ['hard_block_price' => 200, 'soft_block_price' => 215, 'soft_block_release_days' => 7, 'available_seats' => 20],
                $m == 5 => ['hard_block_price' => 205, 'soft_block_price' => 220, 'soft_block_release_days' => 7, 'available_seats' => 20],
                default => ['hard_block_price' => 210, 'soft_block_price' => 230, 'soft_block_release_days' => 7, 'available_seats' => 20],
            };
            $f->update($data);
        }

        // === 2. Update C2 601/602 (IST↔GYD) pricing ===
        foreach (['C2 601', 'C2 602'] as $fn) {
            Flight::where('flight_number', $fn)->get()->each(function ($f) {
                $price = $f->departure_date->month <= 5 ? 150 : 190;
                $f->update(['hard_block_price' => $price, 'price_adult' => $price, 'available_seats' => 20]);
            });
        }

        // === 3. Create C2 603 (GYD→TAS) direct flights ===
        if ($gydId) {
            $gydTas = [
                ['2026-04-20', 150], ['2026-04-27', 150],
                ['2026-05-04', 150], ['2026-05-11', 150], ['2026-05-18', 150], ['2026-05-25', 150],
                ['2026-06-01', 190], ['2026-06-08', 190], ['2026-06-15', 190], ['2026-06-22', 190], ['2026-06-29', 190],
                ['2026-07-06', 190],
            ];
            foreach ($gydTas as [$date, $price]) {
                if (Flight::where('flight_number', 'C2 603')->whereDate('departure_date', $date)->exists()) {
                    continue;
                }
                Flight::create([
                    'airline_id' => $centrumId, 'from_airport_id' => $gydId, 'to_airport_id' => $tasId,
                    'currency_id' => $usdId, 'flight_number' => 'C2 603',
                    'departure_date' => $date, 'departure_time' => '22:00',
                    'arrival_date' => Carbon::parse($date)->addDay()->format('Y-m-d'), 'arrival_time' => '04:00',
                    'price_adult' => $price, 'price_child' => (int) ($price * 0.8), 'price_infant' => 30,
                    'hard_block_price' => $price, 'available_seats' => 20,
                    'class_type' => 'economy', 'is_active' => true,
                ]);
            }
        }

        // === 4. Create C2 701/702 (TAS↔BUS) Batumi flights ===
        if (! $busId) {
            return; // BUS airport missing, skip Batumi
        }

        $batumiFlights = [
            ['2026-05-29', '2026-06-06', 315],
            ['2026-05-31', '2026-06-07', 325],
            ['2026-06-07', '2026-06-14', 325],
            ['2026-06-10', '2026-06-17', 325],
            ['2026-06-14', '2026-06-21', 325],
            ['2026-06-17', '2026-06-24', 325],
            ['2026-06-21', '2026-06-28', 325],
            ['2026-06-24', '2026-07-01', 335],
            ['2026-06-28', '2026-07-05', 335],
        ];

        $georgiaId = DB::table('countries')->where('name_en', 'Georgia')->value('id');
        $batumiCityId = DB::table('cities')->where('name_en', 'Batumi')->value('id');
        $batumiResortId = DB::table('resorts')->where('name_en', 'Batumi Boulevard')->value('id');
        $radissonId = DB::table('hotels')->where('name', 'Radisson Blu Hotel Batumi')->value('id');
        $bbMealId = DB::table('meal_types')->first()?->id;
        $beachTypeId = DB::table('tour_types')->where('name_en', 'Beach Holiday')->value('id') ?? DB::table('tour_types')->first()?->id;
        $standardId = DB::table('program_types')->first()?->id;
        $airplaneId = DB::table('transport_types')->first()?->id;
        $tashkentCityId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $doubleRoomId = DB::table('room_types')->where('name_en', 'Double')->value('id');

        foreach ($batumiFlights as [$dep, $ret, $price]) {
            // Outbound TAS→BUS
            $outbound = Flight::where('flight_number', 'C2 701')->whereDate('departure_date', $dep)->first();
            if (! $outbound) {
                $outbound = Flight::create([
                    'airline_id' => $centrumId, 'from_airport_id' => $tasId, 'to_airport_id' => $busId,
                    'currency_id' => $usdId, 'flight_number' => 'C2 701',
                    'departure_date' => $dep, 'departure_time' => '06:00',
                    'arrival_date' => $dep, 'arrival_time' => '09:00',
                    'price_adult' => $price, 'price_child' => (int) ($price * 0.8), 'price_infant' => 50,
                    'hard_block_price' => $price, 'available_seats' => 20,
                    'class_type' => 'economy', 'is_active' => true,
                ]);
            }

            // Return BUS→TAS
            $return = Flight::where('flight_number', 'C2 702')->whereDate('departure_date', $ret)->first();
            if (! $return) {
                $return = Flight::create([
                    'airline_id' => $centrumId, 'from_airport_id' => $busId, 'to_airport_id' => $tasId,
                    'currency_id' => $usdId, 'flight_number' => 'C2 702',
                    'departure_date' => $ret, 'departure_time' => '22:00',
                    'arrival_date' => Carbon::parse($ret)->addDay()->format('Y-m-d'), 'arrival_time' => '02:00',
                    'price_adult' => $price, 'price_child' => (int) ($price * 0.8), 'price_infant' => 50,
                    'hard_block_price' => $price, 'available_seats' => 20,
                    'class_type' => 'economy', 'is_active' => true,
                ]);
            }

            // === 5. Create Batumi tour ===
            if (! $georgiaId || ! $batumiResortId) {
                continue;
            }

            $nights = Carbon::parse($dep)->diffInDays(Carbon::parse($ret));
            $hotelPricePerNight = $radissonId ? 85 : 70;
            $totalPrice = $price * 2 + $hotelPricePerNight * $nights;

            $tour = Tour::create([
                'tour_type_id' => $beachTypeId, 'program_type_id' => $standardId,
                'country_id' => $georgiaId, 'resort_id' => $batumiResortId,
                'hotel_id' => $radissonId, 'transport_type_id' => $airplaneId,
                'departure_city_id' => $tashkentCityId, 'meal_type_id' => $bbMealId,
                'currency_id' => $usdId, 'nights' => $nights,
                'date_from' => $dep, 'date_to' => $ret,
                'adults' => 2, 'children' => 0, 'price' => $totalPrice,
                'is_available' => true, 'is_hot' => true,
                'instant_confirmation' => true, 'no_stop_sale' => false,
            ]);

            $tour->flights()->attach($outbound->id, ['direction' => 'outbound', 'leg_order' => 1]);
            $tour->flights()->attach($return->id, ['direction' => 'return', 'leg_order' => 2]);

            TourStay::create([
                'tour_id' => $tour->id, 'stay_order' => 1,
                'city_id' => $batumiCityId, 'resort_id' => $batumiResortId,
                'hotel_id' => $radissonId, 'nights' => $nights,
                'meal_type_id' => $bbMealId, 'price_per_person' => $hotelPricePerNight,
                'currency_id' => $usdId,
            ]);

            TourPrice::create([
                'tour_id' => $tour->id, 'room_type_id' => $doubleRoomId,
                'price_adult' => $totalPrice, 'price_child' => round($totalPrice * 0.8),
                'price_infant' => 100, 'availability' => 10,
                'currency_id' => $usdId, 'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Remove Batumi tours
        DB::table('flights')->where('flight_number', 'C2 701')->delete();
        DB::table('flights')->where('flight_number', 'C2 702')->delete();
        DB::table('flights')->where('flight_number', 'C2 603')->delete();

        $radissonId = DB::table('hotels')->where('name', 'Radisson Blu Hotel Batumi')->value('id');
        if ($radissonId) {
            $ids = DB::table('tours')->where('hotel_id', $radissonId)->pluck('id');
            DB::table('tour_prices')->whereIn('tour_id', $ids)->delete();
            DB::table('tour_stays')->whereIn('tour_id', $ids)->delete();
            DB::table('tour_flight')->whereIn('tour_id', $ids)->delete();
            DB::table('tours')->whereIn('id', $ids)->delete();
        }
    }
};
