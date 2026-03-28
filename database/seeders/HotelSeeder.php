<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Standalone hotel seeder for Istanbul, Nice, Baku.
 * Raw DB queries — no Eloquent model dependencies.
 * All NOT NULL columns provided. Safe to re-run.
 */
class HotelSeeder extends Seeder
{
    public function run(): void
    {
        // ── Prerequisites ──
        $usdId = $this->ensureCurrency('USD', 'US Dollar', 'Доллар США', '$');

        $trId = $this->ensureCountry('Turkey', 'Турция', 'TR');
        $frId = $this->ensureCountry('France', 'Франция', 'FR');
        $azId = $this->ensureCountry('Azerbaijan', 'Азербайджан', 'AZ');

        $istanbulId = $this->ensureCity('Istanbul', 'Стамбул', $trId);
        $niceId = $this->ensureCity('Nice', 'Ницца', $frId);
        $bakuId = $this->ensureCity('Baku', 'Баку', $azId);

        $sultanahmetId = $this->ensureResort('Sultanahmet', 'Султанахмет', $trId, $istanbulId);
        $fatihId = $this->ensureResort('Fatih', 'Фатих', $trId, $istanbulId);
        $niceStadeId = $this->ensureResort('Nice Stade', 'Ницца Стад', $frId, $niceId);
        $bakuBlvdId = $this->ensureResort('Baku Boulevard', 'Бакинский бульвар', $azId, $bakuId);

        $star3Id = $this->ensureHotelCategory('3 stars', 3);

        // ── Istanbul Hotels (price = per DBL room with breakfast) ──
        $istanbulHotels = [
            ['name' => 'Grand Liza Hotel', 'price' => 45, 'resort_id' => $fatihId],
            ['name' => 'Grand Emir Hotel', 'price' => 50, 'resort_id' => $fatihId],
            ['name' => 'All Seasons Hotel Istanbul', 'price' => 55, 'resort_id' => $fatihId],
            ['name' => 'New Emin Hotel', 'price' => 61, 'resort_id' => $sultanahmetId],
            ['name' => 'River Hotel', 'price' => 62, 'resort_id' => $fatihId],
            ['name' => 'Grand Washington Hotel', 'price' => 75, 'resort_id' => $sultanahmetId],
            ['name' => 'Sorisso Hotel', 'price' => 75, 'resort_id' => $sultanahmetId],
        ];

        $created = 0;
        foreach ($istanbulHotels as $h) {
            $id = $this->ensureHotel($h['name'], $h['resort_id'], $star3Id, $h['price'], $usdId, $istanbulId);
            if ($id) { $created++; }
        }

        // ── Nice Hotel ──
        $this->ensureHotel('B&B HOTEL Nice Stade Riviera 3 étoiles', $niceStadeId, $star3Id, 110, $usdId, $niceId);
        $created++;

        // ── Baku Hotel ──
        $this->ensureHotel('Nobel Hotel', $bakuBlvdId, $star3Id, 50, $usdId, $bakuId);
        $created++;

        $this->command->info("Ensured {$created} hotels exist.");
    }

    // ── Helper methods (raw DB, all NOT NULL columns provided) ──

    private function ensureCurrency(string $code, string $nameEn, string $nameRu, string $symbol): int
    {
        $row = DB::table('currencies')->where('code', $code)->first();
        if ($row) { return $row->id; }

        return DB::table('currencies')->insertGetId([
            'code' => $code, 'name_en' => $nameEn, 'name_ru' => $nameRu, 'symbol' => $symbol,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureCountry(string $nameEn, string $nameRu, string $code): int
    {
        $row = DB::table('countries')->where('name_en', $nameEn)->first();
        if ($row) { return $row->id; }

        return DB::table('countries')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'code' => $code,
            'is_active' => true, 'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureCity(string $nameEn, string $nameRu, int $countryId): int
    {
        $row = DB::table('cities')->where('name_en', $nameEn)->first();
        if ($row) { return $row->id; }

        return DB::table('cities')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'country_id' => $countryId,
            'is_active' => true, 'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureResort(string $nameEn, string $nameRu, int $countryId, int $cityId): int
    {
        $row = DB::table('resorts')->where('name_en', $nameEn)->first();
        if ($row) { return $row->id; }

        return DB::table('resorts')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'country_id' => $countryId, 'city_id' => $cityId,
            'is_active' => true, 'order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureHotelCategory(string $name, int $stars): int
    {
        $row = DB::table('hotel_categories')->where('stars', $stars)->first();
        if ($row) { return $row->id; }

        return DB::table('hotel_categories')->insertGetId([
            'name' => $name, 'stars' => $stars, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureHotel(string $name, int $resortId, int $categoryId, float $price, int $currencyId, ?int $cityId = null): int
    {
        $row = DB::table('hotels')->where('name', $name)->first();
        if ($row) {
            $update = ['price_per_person' => $price];
            if ($cityId) { $update['city_id'] = $cityId; }
            DB::table('hotels')->where('id', $row->id)->update($update);
            return $row->id;
        }

        return DB::table('hotels')->insertGetId([
            'name' => $name, 'name_en' => $name, 'resort_id' => $resortId,
            'city_id' => $cityId,
            'hotel_category_id' => $categoryId, 'rating' => 3.5, 'is_active' => true,
            'price_per_person' => $price, 'currency_id' => $currencyId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
