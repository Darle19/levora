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

        // ── IST→NCE flights (Turkish Airlines, nonstop, prices from RapidAPI) ──
        $frId = $this->ensureCountry('France', 'Франция', 'FR');
        $niceId = $this->ensureCity('Nice', 'Ницца', $frId);
        $nceId = $this->ensureAirport('NCE', 'Nice Côte d\'Azur Airport', 'Аэропорт Ницца', $niceId);
        $tkId = $this->ensureAirline('TK', 'Turkish Airlines');

        // IST→NCE: +2 days after TAS→IST departure
        $istNceDates = [
            ['2026-04-15', 232], ['2026-04-22', 232], ['2026-04-29', 232],
            ['2026-05-06', 232], ['2026-05-13', 232], ['2026-05-20', 232], ['2026-05-27', 232],
            ['2026-06-03', 232], ['2026-06-10', 232], ['2026-06-17', 232], ['2026-06-24', 232], ['2026-07-01', 232],
        ];

        foreach ($istNceDates as [$date, $price]) {
            if ($this->flightExists($tkId, $istId, $nceId, $date)) { continue; }
            DB::table('flights')->insert([
                'airline_id' => $tkId, 'from_airport_id' => $istId, 'to_airport_id' => $nceId,
                'flight_number' => 'TK 1813', 'departure_date' => $date,
                'departure_time' => '06:55', 'arrival_date' => $date, 'arrival_time' => '08:55',
                'price_adult' => $price, 'currency_id' => $usdId, 'available_seats' => 50,
                'class_type' => 'economy', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $created++;
        }

        // NCE→IST: +6 days after TAS→IST departure
        $nceIstDates = [
            ['2026-04-19', 179], ['2026-04-26', 179], ['2026-05-03', 179],
            ['2026-05-10', 179], ['2026-05-17', 179], ['2026-05-24', 179], ['2026-05-31', 179],
            ['2026-06-07', 179], ['2026-06-14', 179], ['2026-06-21', 179], ['2026-06-28', 179], ['2026-07-05', 179],
        ];

        foreach ($nceIstDates as [$date, $price]) {
            if ($this->flightExists($tkId, $nceId, $istId, $date)) { continue; }
            DB::table('flights')->insert([
                'airline_id' => $tkId, 'from_airport_id' => $nceId, 'to_airport_id' => $istId,
                'flight_number' => 'TK 1814', 'departure_date' => $date,
                'departure_time' => '10:45', 'arrival_date' => $date, 'arrival_time' => '14:45',
                'price_adult' => $price, 'currency_id' => $usdId, 'available_seats' => 50,
                'class_type' => 'economy', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $created++;
        }

        // ── IST→GYD flights (Azerbaijan Airlines J2, one-way, nonstop) ──
        $j2Id = $this->ensureAirline('J2', 'Azerbaijan Airlines');

        $istGydDates = [
            ['2026-04-15', 291], ['2026-04-22', 291], ['2026-04-29', 291],
            ['2026-05-06', 291], ['2026-05-13', 291], ['2026-05-20', 291], ['2026-05-27', 291],
            ['2026-06-03', 291], ['2026-06-10', 291], ['2026-06-17', 291], ['2026-06-24', 291], ['2026-07-01', 291],
        ];

        foreach ($istGydDates as [$date, $price]) {
            if ($this->flightExists($j2Id, $istId, $gydId, $date)) { continue; }
            DB::table('flights')->insert([
                'airline_id' => $j2Id, 'from_airport_id' => $istId, 'to_airport_id' => $gydId,
                'flight_number' => 'J2 76', 'departure_date' => $date,
                'departure_time' => '12:00', 'arrival_date' => $date, 'arrival_time' => '15:50',
                'price_adult' => $price, 'currency_id' => $usdId, 'available_seats' => 50,
                'class_type' => 'economy', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $created++;
        }

        // No GYD→IST flights — Baku tour returns directly GYD→TAS (already created above)

        $this->command->info("Created {$created} flights (TAS→IST + GYD→TAS + IST↔NCE + IST↔GYD).");
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
