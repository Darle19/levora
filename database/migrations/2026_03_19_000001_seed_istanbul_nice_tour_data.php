<?php

use App\Models\Airport;
use App\Models\City;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Resort;
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
        // 1. Nice city
        $niceCity = City::firstOrCreate(
            ['name_en' => 'Nice'],
            ['name_ru' => 'Ницца', 'name_uz' => 'Nitsa', 'country_id' => DB::table('countries')->where('name_en', 'France')->value('id') ?? 6]
        );

        // 2. NCE airport
        $nceAirport = Airport::firstOrCreate(
            ['code' => 'NCE'],
            [
                'name_en' => "Nice Côte d'Azur Airport",
                'name_ru' => 'Аэропорт Ницца Лазурный Берег',
                'name_uz' => "Nitsa Côte d'Azur aeroporti",
                'city_id' => $niceCity->id,
                'is_active' => true,
            ]
        );

        // 3. Nice Stade resort
        $niceResort = Resort::firstOrCreate(
            ['name_en' => 'Nice Stade'],
            [
                'name_ru' => 'Ницца Стад',
                'name_uz' => 'Nitsa Stad',
                'country_id' => $niceCity->country_id,
                'city_id' => $niceCity->id,
                'is_active' => true,
            ]
        );

        // 4. B&B HOTEL Nice
        $hotel = Hotel::firstOrCreate(
            ['name' => 'B&B HOTEL Nice Stade Riviera 3 étoiles'],
            [
                'name_en' => 'B&B HOTEL Nice Stade Riviera 3 étoiles',
                'name_ru' => 'B&B HOTEL Nice Stade Riviera 3 звезды',
                'name_uz' => 'B&B HOTEL Nice Stade Riviera 3 yulduz',
                'description' => '3-star hotel in Nice near Stade area',
                'address' => 'Nice, France',
                'resort_id' => $niceResort->id,
                'hotel_category_id' => DB::table('hotel_categories')->where('stars', 3)->value('id') ?? 3,
                'rating' => 3.5,
                'is_active' => true,
                'price_per_person' => 110.00,
                'currency_id' => DB::table('currencies')->where('code', 'USD')->value('id') ?? 1,
            ]
        );

        // Ensure Centrum Air airline exists
        $centrumAirId = DB::table('airlines')->where('code', 'C2')->value('id');
        if (! $centrumAirId) {
            $centrumAirId = DB::table('airlines')->insertGetId([
                'name' => 'Centrum Air',
                'code' => 'C2',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure France country exists
        $franceId = DB::table('countries')->where('name_en', 'France')->value('id');
        if (! $franceId) {
            $franceId = DB::table('countries')->insertGetId([
                'name_en' => 'France',
                'name_ru' => 'Франция',
                'name_uz' => 'Frantsiya',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Reference IDs (bail if critical data missing)
        $tasAirportId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istAirportId = DB::table('airports')->where('code', 'IST')->value('id');
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');

        if (! $tasAirportId || ! $istAirportId || ! $usdId) {
            throw new \RuntimeException('Missing critical reference data: TAS/IST airports or USD currency. Run BasicDataSeeder first.');
        }

        $istanbulCityId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $sultanahmetId = DB::table('resorts')->where('name_en', 'Sultanahmet')->value('id');
        $bbMealId = DB::table('meal_types')->where('name_en', 'Bed & Breakfast')->value('id') ?? DB::table('meal_types')->first()?->id;
        $combinedTypeId = DB::table('tour_types')->where('name_en', 'Combined')->value('id') ?? DB::table('tour_types')->first()?->id;
        $standardProgramId = DB::table('program_types')->where('name_en', 'Standard')->value('id') ?? DB::table('program_types')->first()?->id;
        $airplaneId = DB::table('transport_types')->where('name_en', 'Airplane')->value('id') ?? DB::table('transport_types')->first()?->id;
        $tashkentCityId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $doubleRoomId = DB::table('room_types')->where('name_en', 'Double')->value('id');

        // 5. Create flights + tours for 12 Mondays
        $monday = Carbon::now()->next(Carbon::MONDAY);

        for ($i = 0; $i < 12; $i++) {
            $departDate = $monday->copy()->addWeeks($i);
            $returnDate = $departDate->copy()->addDays(6);

            // Outbound: TAS → IST
            $outbound = Flight::create([
                'airline_id' => $centrumAirId,
                'from_airport_id' => $tasAirportId,
                'to_airport_id' => $istAirportId,
                'currency_id' => $usdId,
                'flight_number' => 'C2 501',
                'departure_date' => $departDate->format('Y-m-d'),
                'departure_time' => '06:00',
                'arrival_date' => $departDate->format('Y-m-d'),
                'arrival_time' => '10:00',
                'price_adult' => 200.00,
                'price_child' => 160.00,
                'price_infant' => 40.00,
                'hard_block_price' => 180.00,
                'available_seats' => 30,
                'class_type' => 'economy',
                'is_active' => true,
            ]);

            // Return: IST → TAS
            $return = Flight::create([
                'airline_id' => $centrumAirId,
                'from_airport_id' => $istAirportId,
                'to_airport_id' => $tasAirportId,
                'currency_id' => $usdId,
                'flight_number' => 'C2 502',
                'departure_date' => $returnDate->format('Y-m-d'),
                'departure_time' => '22:00',
                'arrival_date' => $returnDate->copy()->addDay()->format('Y-m-d'),
                'arrival_time' => '04:00',
                'price_adult' => 200.00,
                'price_child' => 160.00,
                'price_infant' => 40.00,
                'hard_block_price' => 180.00,
                'available_seats' => 30,
                'class_type' => 'economy',
                'is_active' => true,
            ]);

            // Tour
            $tour = Tour::create([
                'tour_type_id' => $combinedTypeId,
                'program_type_id' => $standardProgramId,
                'country_id' => $franceId,
                'resort_id' => $niceResort->id,
                'hotel_id' => $hotel->id,
                'transport_type_id' => $airplaneId,
                'departure_city_id' => $tashkentCityId,
                'meal_type_id' => $bbMealId,
                'currency_id' => $usdId,
                'nights' => 7,
                'date_from' => $departDate->format('Y-m-d'),
                'date_to' => $returnDate->format('Y-m-d'),
                'adults' => 2,
                'children' => 0,
                'price' => 950.00,
                'is_available' => true,
                'is_hot' => false,
                'instant_confirmation' => true,
                'no_stop_sale' => false,
            ]);

            // Attach flights
            $tour->flights()->attach($outbound->id, ['direction' => 'outbound', 'leg_order' => 1]);
            $tour->flights()->attach($return->id, ['direction' => 'return', 'leg_order' => 4]);

            // Amadeus segments: IST↔NCE
            TourAmadeusSegment::create([
                'tour_id' => $tour->id,
                'leg_order' => 2,
                'origin_airport_id' => $istAirportId,
                'destination_airport_id' => $nceAirport->id,
                'is_active' => true,
            ]);
            TourAmadeusSegment::create([
                'tour_id' => $tour->id,
                'leg_order' => 3,
                'origin_airport_id' => $nceAirport->id,
                'destination_airport_id' => $istAirportId,
                'is_active' => true,
            ]);

            // Stays
            TourStay::create([
                'tour_id' => $tour->id,
                'stay_order' => 1,
                'city_id' => $istanbulCityId,
                'resort_id' => $sultanahmetId,
                'nights' => 2,
                'meal_type_id' => $bbMealId,
            ]);
            TourStay::create([
                'tour_id' => $tour->id,
                'stay_order' => 2,
                'city_id' => $niceCity->id,
                'resort_id' => $niceResort->id,
                'hotel_id' => $hotel->id,
                'nights' => 5,
                'meal_type_id' => $bbMealId,
                'price_per_person' => 110.00,
                'currency_id' => $usdId,
            ]);

            // Tour price (Double room)
            TourPrice::create([
                'tour_id' => $tour->id,
                'room_type_id' => $doubleRoomId,
                'price_adult' => 950.00,
                'price_child' => 760.00,
                'price_infant' => 100.00,
                'availability' => 15,
                'currency_id' => $usdId,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Remove tours by matching hotel
        $hotelId = DB::table('hotels')->where('name', 'B&B HOTEL Nice Stade Riviera 3 étoiles')->value('id');
        if ($hotelId) {
            $tourIds = DB::table('tours')->where('hotel_id', $hotelId)->pluck('id');
            DB::table('tour_prices')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tour_stays')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tour_amadeus_segments')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tour_flight')->whereIn('tour_id', $tourIds)->delete();
            DB::table('tours')->whereIn('id', $tourIds)->delete();
        }

        // Remove flights
        DB::table('flights')->where('flight_number', 'C2 501')->delete();
        DB::table('flights')->where('flight_number', 'C2 502')->delete();

        // Remove hotel, resort, airport, city
        if ($hotelId) {
            DB::table('hotels')->where('id', $hotelId)->delete();
        }
        DB::table('resorts')->where('name_en', 'Nice Stade')->delete();
        DB::table('airports')->where('code', 'NCE')->delete();
        DB::table('cities')->where('name_en', 'Nice')->delete();
    }
};
