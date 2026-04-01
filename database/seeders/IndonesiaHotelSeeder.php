<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Indonesia hotels for Bali and Lombok.
 * Creates: country, cities, resorts (regions), hotel categories, room types, hotels with prices.
 * All prices are DBL room per night with BB (breakfast).
 */
class IndonesiaHotelSeeder extends Seeder
{
    public function run(): void
    {
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        if (! $usdId) {
            $this->command->error('USD currency not found.');
            return;
        }

        // Country
        $idId = DB::table('countries')->where('code', 'ID')->value('id');
        if (! $idId) {
            $idId = DB::table('countries')->insertGetId([
                'name_en' => 'Indonesia', 'name_ru' => 'Индонезия', 'name_uz' => 'Indoneziya',
                'code' => 'ID', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Cities: Bali and Lombok
        $baliId = $this->ensureCity('Bali', 'Бали', 'Bali', $idId);
        $lombokId = $this->ensureCity('Lombok', 'Ломбок', 'Lombok', $idId);

        // Hotel categories (stars)
        $star3 = $this->ensureCategory(3);
        $star4 = $this->ensureCategory(4);
        $star5 = $this->ensureCategory(5);

        // Room types
        $dblId = $this->ensureRoomType('DBL', 'Double Room', 2, 0);
        $sglId = $this->ensureRoomType('SGL', 'Single Room', 1, 0);

        // Meal type BB
        $bbId = DB::table('meal_types')->where('code', 'BB')->value('id');
        if (! $bbId) {
            $bbId = DB::table('meal_types')->insertGetId([
                'name_en' => 'Bed & Breakfast', 'name_ru' => 'Завтрак', 'name_uz' => 'Nonushta',
                'code' => 'BB', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ═══ BALI REGIONS ═══
        $kutaResort = $this->ensureResort('Kuta', 'Кута', $idId, $baliId);
        $kartikaResort = $this->ensureResort('Kartika Plaza', 'Картика Плаза', $idId, $baliId);
        $legianResort = $this->ensureResort('Legian', 'Легиан', $idId, $baliId);
        $ubudResort = $this->ensureResort('Ubud', 'Убуд', $idId, $baliId);
        $nusaDuaResort = $this->ensureResort('Nusa Dua', 'Нуса Дуа', $idId, $baliId);
        $lovinaResort = $this->ensureResort('Lovina', 'Ловина', $idId, $baliId);

        // ═══ LOMBOK REGIONS ═══
        $senggigiResort = $this->ensureResort('Senggigi', 'Сенгиги', $idId, $lombokId);
        $lombokResort = $this->ensureResort('Lombok', 'Ломбок', $idId, $lombokId);

        // ═══ BALI HOTELS ═══
        $baliHotels = [
            // Kuta
            ['Swiss-Belinn Express', $kutaResort, $baliId, $star3, 33],
            ['Quest Hotel Kuta', $kutaResort, $baliId, $star3, 34],
            ['Eden Hotel Kuta', $kutaResort, $baliId, $star3, 36],
            ['Bedrock Hotel Kuta', $kutaResort, $baliId, $star3, 34],
            ['Kutabex Beachfront', $kutaResort, $baliId, $star3, 36],
            ['Crystal Hotel Kuta', $kutaResort, $baliId, $star3, 30],
            ['Episode Hotel Kuta', $kutaResort, $baliId, $star3, 36],
            ['Neo Hotel Kuta', $kutaResort, $baliId, $star3, 22],
            // Kartika Plaza
            ['Face Kartika Plaza', $kartikaResort, $baliId, $star3, 36],
            // Legian
            ['J4 Hotel Legian', $legianResort, $baliId, $star3, 36],
            ['The One Legian', $legianResort, $baliId, $star3, 36],
            // Ubud
            ['The Sawah Resort Ubud', $ubudResort, $baliId, $star3, 36],
            // Nusa Dua
            ['Tanadewa Nusa Dua', $nusaDuaResort, $baliId, $star3, 175],
            // Lovina
            ['Bali Taman Lovina', $lovinaResort, $baliId, $star3, 36],
            ['New Sunari Lovina', $lovinaResort, $baliId, $star3, 36],
            ['Brits Resort Lovina', $lovinaResort, $baliId, $star3, 44],
            ['Bumi Rumi Lovina', $lovinaResort, $baliId, $star3, 30],
            ['Shri Ganesh Lovina', $lovinaResort, $baliId, $star3, 30],
        ];

        // ═══ LOMBOK HOTELS ═══
        $lombokHotels = [
            ['Louis Kienne Resort Senggigi', $senggigiResort, $lombokId, $star4, 59],
            ['Holiday Resort Senggigi', $senggigiResort, $lombokId, $star4, 71],
            ['Merumatta Senggigi', $senggigiResort, $lombokId, $star4, 71],
            ['Sheraton Senggigi', $senggigiResort, $lombokId, $star4, 80],
            ['Katamaran Resort Senggigi', $senggigiResort, $lombokId, $star5, 112],
            ['Qunci Villas', $senggigiResort, $lombokId, $star5, 180],
            ['Royal Avila', $lombokResort, $lombokId, $star5, 151],
            ['Kalandara Resort Lombok', $lombokResort, $lombokId, $star5, 239],
        ];

        $created = 0;
        foreach (array_merge($baliHotels, $lombokHotels) as [$name, $resortId, $cityId, $catId, $dblPrice]) {
            $existing = DB::table('hotels')->where('name_en', $name)->first();
            if ($existing) {
                continue;
            }

            $hotelId = DB::table('hotels')->insertGetId([
                'name' => $name,
                'name_en' => $name,
                'name_ru' => $name,
                'name_uz' => $name,
                'resort_id' => $resortId,
                'city_id' => $cityId,
                'hotel_category_id' => $catId,
                'price_per_person' => $dblPrice,
                'currency_id' => $usdId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // DBL room type: same price as hotel base
            DB::table('hotel_room_type')->insert([
                'hotel_id' => $hotelId,
                'room_type_id' => $dblId,
                'price_per_night' => $dblPrice,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // SGL room type: full room price (same as DBL since 1 person occupies whole room)
            DB::table('hotel_room_type')->insert([
                'hotel_id' => $hotelId,
                'room_type_id' => $sglId,
                'price_per_night' => $dblPrice,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link BB meal type
            DB::table('hotel_meal_type')->insertOrIgnore([
                'hotel_id' => $hotelId,
                'meal_type_id' => $bbId,
            ]);

            $created++;
        }

        $this->command->info("Created {$created} Indonesia hotels (Bali + Lombok).");
    }

    private function ensureCity(string $nameEn, string $nameRu, string $nameUz, int $countryId): int
    {
        $row = DB::table('cities')->where('name_en', $nameEn)->where('country_id', $countryId)->first();
        if ($row) return $row->id;

        return DB::table('cities')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'name_uz' => $nameUz,
            'country_id' => $countryId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureResort(string $nameEn, string $nameRu, int $countryId, int $cityId): int
    {
        $row = DB::table('resorts')->where('name_en', $nameEn)->where('city_id', $cityId)->first();
        if ($row) return $row->id;

        return DB::table('resorts')->insertGetId([
            'name_en' => $nameEn, 'name_ru' => $nameRu, 'name_uz' => $nameEn,
            'country_id' => $countryId, 'city_id' => $cityId,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureCategory(int $stars): int
    {
        $row = DB::table('hotel_categories')->where('stars', $stars)->first();
        if ($row) return $row->id;

        return DB::table('hotel_categories')->insertGetId([
            'name' => $stars . ' Star', 'name_en' => $stars . ' Star',
            'name_ru' => $stars . ' Звезды', 'name_uz' => $stars . ' Yulduz',
            'stars' => $stars, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ensureRoomType(string $code, string $name, int $maxAdults, int $maxChildren): int
    {
        $row = DB::table('room_types')->where('code', $code)->first();
        if ($row) return $row->id;

        return DB::table('room_types')->insertGetId([
            'code' => $code, 'name_en' => $name, 'name_ru' => $name, 'name_uz' => $name,
            'max_adults' => $maxAdults, 'max_children' => $maxChildren,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
