<?php

use App\Models\Flight;
use App\Models\Tour;
use App\Models\TourAmadeusSegment;
use App\Models\TourPrice;
use App\Models\TourStay;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reference IDs
        $centrumAirId = DB::table('airlines')->where('code', 'C2')->value('id');
        $tasAirportId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istAirportId = DB::table('airports')->where('code', 'IST')->value('id');
        $dpsAirportId = DB::table('airports')->where('code', 'DPS')->value('id');
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        $indonesiaId = DB::table('countries')->where('name_en', 'Indonesia')->value('id');
        $istanbulCityId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $baliCityId = DB::table('cities')->where('name_en', 'Denpasar')->value('id');
        $sultanahmetId = DB::table('resorts')->where('name_en', 'Sultanahmet')->value('id');
        $kutaId = DB::table('resorts')->where('name_en', 'Kuta')->value('id');
        $hardRockId = DB::table('hotels')->where('name', 'Hard Rock Hotel Bali')->value('id');
        $bbMealId = DB::table('meal_types')->first()?->id;
        $combinedTypeId = DB::table('tour_types')->where('name_en', 'Combined')->value('id') ?? DB::table('tour_types')->first()?->id;
        $standardProgramId = DB::table('program_types')->first()?->id;
        $airplaneId = DB::table('transport_types')->first()?->id;
        $tashkentCityId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $doubleRoomId = DB::table('room_types')->where('name_en', 'Double')->value('id');

        if (! $centrumAirId || ! $tasAirportId || ! $istAirportId || ! $dpsAirportId || ! $usdId) {
            throw new \RuntimeException('Missing critical reference data for Istanbul+Bali tours.');
        }

        $monday = Carbon::now()->next(Carbon::MONDAY);

        for ($i = 0; $i < 12; $i++) {
            $dep = $monday->copy()->addWeeks($i);
            $ret = $dep->copy()->addDays(6);

            // Reuse existing TAS↔IST flights or create new ones
            $outbound = Flight::where('flight_number', 'C2 501')
                ->whereDate('departure_date', $dep->format('Y-m-d'))->first();
            $return = Flight::where('flight_number', 'C2 502')
                ->whereDate('departure_date', $ret->format('Y-m-d'))->first();

            if (! $outbound) {
                $outbound = Flight::create([
                    'airline_id' => $centrumAirId, 'from_airport_id' => $tasAirportId,
                    'to_airport_id' => $istAirportId, 'currency_id' => $usdId,
                    'flight_number' => 'C2 501', 'departure_date' => $dep->format('Y-m-d'),
                    'departure_time' => '06:00', 'arrival_date' => $dep->format('Y-m-d'),
                    'arrival_time' => '10:00', 'price_adult' => 200, 'price_child' => 160,
                    'price_infant' => 40, 'hard_block_price' => 180, 'available_seats' => 30,
                    'class_type' => 'economy', 'is_active' => true,
                ]);
            }
            if (! $return) {
                $return = Flight::create([
                    'airline_id' => $centrumAirId, 'from_airport_id' => $istAirportId,
                    'to_airport_id' => $tasAirportId, 'currency_id' => $usdId,
                    'flight_number' => 'C2 502', 'departure_date' => $ret->format('Y-m-d'),
                    'departure_time' => '22:00', 'arrival_date' => $ret->copy()->addDay()->format('Y-m-d'),
                    'arrival_time' => '04:00', 'price_adult' => 200, 'price_child' => 160,
                    'price_infant' => 40, 'hard_block_price' => 180, 'available_seats' => 30,
                    'class_type' => 'economy', 'is_active' => true,
                ]);
            }

            $tour = Tour::create([
                'tour_type_id' => $combinedTypeId, 'program_type_id' => $standardProgramId,
                'country_id' => $indonesiaId, 'resort_id' => $kutaId, 'hotel_id' => $hardRockId,
                'transport_type_id' => $airplaneId, 'departure_city_id' => $tashkentCityId,
                'meal_type_id' => $bbMealId, 'currency_id' => $usdId,
                'nights' => 7, 'date_from' => $dep->format('Y-m-d'), 'date_to' => $ret->format('Y-m-d'),
                'adults' => 2, 'children' => 0, 'price' => 775.00,
                'is_available' => true, 'is_hot' => true, 'instant_confirmation' => true, 'no_stop_sale' => false,
            ]);

            $tour->flights()->attach($outbound->id, ['direction' => 'outbound', 'leg_order' => 1]);
            $tour->flights()->attach($return->id, ['direction' => 'return', 'leg_order' => 4]);

            TourAmadeusSegment::create(['tour_id' => $tour->id, 'leg_order' => 2, 'origin_airport_id' => $istAirportId, 'destination_airport_id' => $dpsAirportId, 'is_active' => true]);
            TourAmadeusSegment::create(['tour_id' => $tour->id, 'leg_order' => 3, 'origin_airport_id' => $dpsAirportId, 'destination_airport_id' => $istAirportId, 'is_active' => true]);

            TourStay::create(['tour_id' => $tour->id, 'stay_order' => 1, 'city_id' => $istanbulCityId, 'resort_id' => $sultanahmetId, 'nights' => 2, 'meal_type_id' => $bbMealId]);
            TourStay::create(['tour_id' => $tour->id, 'stay_order' => 2, 'city_id' => $baliCityId, 'resort_id' => $kutaId, 'hotel_id' => $hardRockId, 'nights' => 5, 'meal_type_id' => $bbMealId, 'price_per_person' => 75.00, 'currency_id' => $usdId]);

            TourPrice::create(['tour_id' => $tour->id, 'room_type_id' => $doubleRoomId, 'price_adult' => 775.00, 'price_child' => 620.00, 'price_infant' => 100.00, 'availability' => 15, 'currency_id' => $usdId, 'is_active' => true]);
        }
    }

    public function down(): void
    {
        $hardRockId = DB::table('hotels')->where('name', 'Hard Rock Hotel Bali')->value('id');
        if ($hardRockId) {
            $tourIds = DB::table('tours')->where('hotel_id', $hardRockId)->pluck('id');
            DB::table('tour_prices')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tour_stays')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tour_amadeus_segments')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tour_flight')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tours')->whereIn('id', $tourIds)->delete();
        }
    }
};
