<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Airline;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightBlockSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data
        $usd = Currency::where('code', 'USD')->firstOrFail();
        $airline = Airline::where('code', 'HY')->firstOrFail();

        // Ensure airports exist
        $tas = Airport::where('code', 'TAS')->firstOrFail();
        $ist = Airport::where('code', 'IST')->firstOrFail();

        // Bukhara airport (BUS)
        $uzbekistan = Country::where('code', 'UZB')->firstOrFail();
        $bukhara = City::firstOrCreate(
            ['name_en' => 'Bukhara'],
            ['country_id' => $uzbekistan->id, 'name_ru' => 'Бухара', 'name_uz' => 'Buxoro', 'is_active' => true]
        );
        $bus = Airport::firstOrCreate(
            ['code' => 'BUS'],
            ['city_id' => $bukhara->id, 'name_en' => 'Bukhara International Airport', 'name_ru' => 'Международный аэропорт Бухары', 'name_uz' => 'Buxoro xalqaro aeroporti', 'is_active' => true]
        );

        // Baku airport (GYD)
        $azerbaijan = Country::firstOrCreate(
            ['code' => 'AZE'],
            ['name_en' => 'Azerbaijan', 'name_ru' => 'Азербайджан', 'name_uz' => 'Ozarbayjon', 'is_active' => true, 'order' => 7]
        );
        $baku = City::firstOrCreate(
            ['name_en' => 'Baku'],
            ['country_id' => $azerbaijan->id, 'name_ru' => 'Баку', 'name_uz' => 'Boku', 'is_active' => true]
        );
        $gyd = Airport::firstOrCreate(
            ['code' => 'GYD'],
            ['city_id' => $baku->id, 'name_en' => 'Heydar Aliyev International Airport', 'name_ru' => 'Международный аэропорт Гейдар Алиев', 'name_uz' => 'Haydar Aliyev xalqaro aeroporti', 'is_active' => true]
        );

        $defaults = [
            'airline_id' => $airline->id,
            'currency_id' => $usd->id,
            'available_seats' => 20,
            'class_type' => 'economy',
            'is_active' => true,
        ];

        // ── TAS-BUS-TAS (round trips) ──
        $tasBusFlights = [
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

        foreach ($tasBusFlights as $i => [$depDate, $retDate, $hardPrice]) {
            $num = $i + 1;

            // Outbound: TAS → BUS
            Flight::create(array_merge($defaults, [
                'from_airport_id' => $tas->id,
                'to_airport_id' => $bus->id,
                'flight_number' => "HY{$num}01",
                'departure_date' => $depDate,
                'departure_time' => '08:00',
                'arrival_time' => '09:15',
                'arrival_date' => $depDate,
                'price_adult' => $hardPrice,
                'hard_block_price' => $hardPrice,
            ]));

            // Return: BUS → TAS
            Flight::create(array_merge($defaults, [
                'from_airport_id' => $bus->id,
                'to_airport_id' => $tas->id,
                'flight_number' => "HY{$num}02",
                'departure_date' => $retDate,
                'departure_time' => '18:00',
                'arrival_time' => '19:15',
                'arrival_date' => $retDate,
                'price_adult' => $hardPrice,
                'hard_block_price' => $hardPrice,
            ]));
        }

        // ── TAS-IST (one-way) ──
        $tasIstFlights = [
            ['2026-04-13', 200, 215],
            ['2026-04-20', 200, 215],
            ['2026-04-27', 200, 215],
            ['2026-05-04', 205, 220],
            ['2026-05-11', 205, 220],
            ['2026-05-18', 205, 220],
            ['2026-05-25', 205, 220],
            ['2026-06-01', 210, 230],
            ['2026-06-08', 210, 230],
            ['2026-06-15', 210, 230],
            ['2026-06-22', 210, 230],
            ['2026-06-29', 210, 230],
        ];

        foreach ($tasIstFlights as $i => [$depDate, $hardPrice, $softPrice]) {
            $num = $i + 1;

            Flight::create(array_merge($defaults, [
                'from_airport_id' => $tas->id,
                'to_airport_id' => $ist->id,
                'flight_number' => "HY{$num}10",
                'departure_date' => $depDate,
                'departure_time' => '10:00',
                'arrival_time' => '14:30',
                'arrival_date' => $depDate,
                'price_adult' => $hardPrice,
                'hard_block_price' => $hardPrice,
                'soft_block_price' => $softPrice,
                'soft_block_release_days' => 7,
            ]));
        }

        // ── GYD-TAS (one-way) ──
        $gydTasFlights = [
            ['2026-04-20', 150],
            ['2026-04-27', 150],
            ['2026-05-04', 150],
            ['2026-05-11', 150],
            ['2026-05-18', 150],
            ['2026-05-25', 150],
            ['2026-06-01', 190],
            ['2026-06-08', 190],
            ['2026-06-15', 190],
            ['2026-06-22', 190],
            ['2026-06-29', 190],
            ['2026-07-06', 190],
        ];

        foreach ($gydTasFlights as $i => [$depDate, $hardPrice]) {
            $num = $i + 1;

            Flight::create(array_merge($defaults, [
                'from_airport_id' => $gyd->id,
                'to_airport_id' => $tas->id,
                'flight_number' => "HY{$num}20",
                'departure_date' => $depDate,
                'departure_time' => '12:00',
                'arrival_time' => '16:30',
                'arrival_date' => $depDate,
                'price_adult' => $hardPrice,
                'hard_block_price' => $hardPrice,
            ]));
        }

        $this->command->info('Flight block data seeded: 18 TAS-BUS-TAS + 12 TAS-IST + 12 GYD-TAS = 42 flights.');
    }
}
