<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Standalone flight seeder for block seats: TAS→IST and GYD→TAS.
 * Creates all prerequisite reference data inline — no dependencies on other seeders.
 * Safe to re-run (uses firstOrCreate pattern via raw DB).
 */
class FlightSeeder extends Seeder
{
    public function run(): void
    {
        // ── Reference data (all NOT NULL columns provided) ──

        $usdId = $this->ensureCurrency('USD', 'US Dollar', 'Доллар США', '$');

        $uzId = $this->ensureCountry('Uzbekistan', 'Узбекистан', 'UZ');
        $trId = $this->ensureCountry('Turkey', 'Турция', 'TR');
        $azId = $this->ensureCountry('Azerbaijan', 'Азербайджан', 'AZ');

        $tashkentId = $this->ensureCity('Tashkent', 'Ташкент', $uzId);
        $istanbulId = $this->ensureCity('Istanbul', 'Стамбул', $trId);
        $bakuId = $this->ensureCity('Baku', 'Баку', $azId);

        $tasId = $this->ensureAirport('TAS', 'Tashkent International Airport', 'Международный аэропорт Ташкент', $tashkentId);
        $istId = $this->ensureAirport('IST', 'Istanbul Airport', 'Аэропорт Стамбул', $istanbulId);
        $gydId = $this->ensureAirport('GYD', 'Heydar Aliyev International Airport', 'Международный аэропорт Гейдар Алиев', $bakuId);

        $c2Id = $this->ensureAirline('C2', 'Centrum Air');

        // ── TAS→IST flights (12 dates, $215–$230, 20 seats) ──
        $tasIst = [
            ['2026-04-13', 215], ['2026-04-20', 215], ['2026-04-27', 215],
            ['2026-05-04', 220], ['2026-05-11', 220], ['2026-05-18', 220], ['2026-05-25', 220],
            ['2026-06-01', 230], ['2026-06-08', 230], ['2026-06-15', 230], ['2026-06-22', 230], ['2026-06-29', 230],
        ];

        $created = 0;
        foreach ($tasIst as [$date, $price]) {
            if ($this->flightExists($c2Id, $tasId, $istId, $date)) {
                continue;
            }
            DB::table('flights')->insert([
                'airline_id' => $c2Id,
                'from_airport_id' => $tasId,
                'to_airport_id' => $istId,
                'flight_number' => 'C2 501',
                'departure_date' => $date,
                'departure_time' => '08:00',
                'arrival_date' => $date,
                'arrival_time' => '11:30',
                'price_adult' => $price,
                'soft_block_price' => $price,
                'hard_block_price' => $price,
                'currency_id' => $usdId,
                'available_seats' => 20,
                'class_type' => 'economy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        // ── GYD→TAS flights (12 dates, $180–$190, 20 seats) ──
        $gydTas = [
            ['2026-04-20', 180], ['2026-04-27', 180],
            ['2026-05-04', 180], ['2026-05-11', 180], ['2026-05-18', 180], ['2026-05-25', 180],
            ['2026-06-01', 190], ['2026-06-08', 190], ['2026-06-15', 190], ['2026-06-22', 190], ['2026-06-29', 190],
            ['2026-07-06', 190],
        ];

        foreach ($gydTas as [$date, $price]) {
            if ($this->flightExists($c2Id, $gydId, $tasId, $date)) {
                continue;
            }
            DB::table('flights')->insert([
                'airline_id' => $c2Id,
                'from_airport_id' => $gydId,
                'to_airport_id' => $tasId,
                'flight_number' => 'C2 603',
                'departure_date' => $date,
                'departure_time' => '14:00',
                'arrival_date' => $date,
                'arrival_time' => '18:30',
                'price_adult' => $price,
                'soft_block_price' => $price,
                'hard_block_price' => $price,
                'currency_id' => $usdId,
                'available_seats' => 20,
                'class_type' => 'economy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        $this->command->info("Created {$created} flights (TAS→IST + GYD→TAS).");
    }

    private function ensureCurrency(string $code, string $nameEn, string $nameRu, string $symbol): int
    {
        $row = DB::table('currencies')->where('code', $code)->first();
        if ($row) {
            return $row->id;
        }

        return DB::table('currencies')->insertGetId([
            'code' => $code, 'name_en' => $nameEn, 'name_ru' => $nameRu, 'symbol' => $symbol,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureCountry(string $nameEn, string $nameRu, string $code): int
    {
        $row = DB::table('countries')->where('name_en', $nameEn)->first();
        if ($row) {
            return $row->id;
        }

        return DB::table('countries')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'code' => $code,
            'is_active' => true, 'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureCity(string $nameEn, string $nameRu, int $countryId): int
    {
        $row = DB::table('cities')->where('name_en', $nameEn)->first();
        if ($row) {
            return $row->id;
        }

        return DB::table('cities')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'country_id' => $countryId,
            'is_active' => true, 'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureAirport(string $code, string $nameEn, string $nameRu, int $cityId): int
    {
        $row = DB::table('airports')->where('code', $code)->first();
        if ($row) {
            return $row->id;
        }

        return DB::table('airports')->insertGetId([
            'code' => $code, 'name_en' => $nameEn, 'name_ru' => $nameRu, 'city_id' => $cityId,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureAirline(string $code, string $name): int
    {
        $row = DB::table('airlines')->where('code', $code)->first();
        if ($row) {
            return $row->id;
        }

        return DB::table('airlines')->insertGetId([
            'code' => $code, 'name' => $name,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function flightExists(int $airlineId, int $fromId, int $toId, string $date): bool
    {
        return DB::table('flights')
            ->where('airline_id', $airlineId)
            ->where('from_airport_id', $fromId)
            ->where('to_airport_id', $toId)
            ->where('departure_date', $date)
            ->exists();
    }
}
