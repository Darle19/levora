<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reference data only — no test flights, hotels, or tours.
 * Real data created by: FlightSeeder → HotelSeeder → FlightPathSeeder
 */
class BasicDataSeeder extends Seeder
{
    public function run(): void
    {
        // Currencies
        DB::table('currencies')->insert([
            ['code' => 'USD', 'name_en' => 'US Dollar', 'name_ru' => 'Доллар США', 'symbol' => '$', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'UZS', 'name_en' => 'Uzbek Som', 'name_ru' => 'Узбекский сум', 'symbol' => 'сўм', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name_en' => 'Euro', 'name_ru' => 'Евро', 'symbol' => '€', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Countries
        DB::table('countries')->insert([
            ['name_en' => 'Uzbekistan', 'name_ru' => 'Узбекистан', 'code' => 'UZ', 'is_active' => true, 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name_en' => 'Turkey', 'name_ru' => 'Турция', 'code' => 'TR', 'is_active' => true, 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name_en' => 'France', 'name_ru' => 'Франция', 'code' => 'FR', 'is_active' => true, 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name_en' => 'Azerbaijan', 'name_ru' => 'Азербайджан', 'code' => 'AZ', 'is_active' => true, 'order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $uzId = DB::table('countries')->where('name_en', 'Uzbekistan')->value('id');
        $trId = DB::table('countries')->where('name_en', 'Turkey')->value('id');
        $frId = DB::table('countries')->where('name_en', 'France')->value('id');
        $azId = DB::table('countries')->where('name_en', 'Azerbaijan')->value('id');

        // Cities
        DB::table('cities')->insert([
            ['name_en' => 'Tashkent', 'name_ru' => 'Ташкент', 'country_id' => $uzId, 'is_active' => true, 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name_en' => 'Istanbul', 'name_ru' => 'Стамбул', 'country_id' => $trId, 'is_active' => true, 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name_en' => 'Nice', 'name_ru' => 'Ницца', 'country_id' => $frId, 'is_active' => true, 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name_en' => 'Baku', 'name_ru' => 'Баку', 'country_id' => $azId, 'is_active' => true, 'order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $tashkentId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $istanbulId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $niceId = DB::table('cities')->where('name_en', 'Nice')->value('id');
        $bakuId = DB::table('cities')->where('name_en', 'Baku')->value('id');

        // Airports
        DB::table('airports')->insert([
            ['code' => 'TAS', 'name_en' => 'Tashkent International Airport', 'name_ru' => 'Международный аэропорт Ташкент', 'city_id' => $tashkentId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'IST', 'name_en' => 'Istanbul Airport', 'name_ru' => 'Аэропорт Стамбул', 'city_id' => $istanbulId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'NCE', 'name_en' => 'Nice Côte d\'Azur Airport', 'name_ru' => 'Аэропорт Ницца', 'city_id' => $niceId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GYD', 'name_en' => 'Heydar Aliyev Airport', 'name_ru' => 'Аэропорт Гейдар Алиев', 'city_id' => $bakuId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Hotel Categories
        DB::table('hotel_categories')->insert([
            ['name' => '5 stars', 'stars' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '4 stars', 'stars' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '3 stars', 'stars' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Meal Types
        DB::table('meal_types')->insert([
            ['code' => 'BB', 'name_en' => 'Bed & Breakfast', 'name_ru' => 'Завтрак', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'HB', 'name_en' => 'Half Board', 'name_ru' => 'Полупансион', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FB', 'name_en' => 'Full Board', 'name_ru' => 'Полный пансион', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'AI', 'name_en' => 'All Inclusive', 'name_ru' => 'Все включено', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'RO', 'name_en' => 'Room Only', 'name_ru' => 'Без питания', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Program Types
        DB::table('program_types')->insert([
            ['name_en' => 'Standard', 'name_ru' => 'Стандарт', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Transport Types
        DB::table('transport_types')->insert([
            ['name_en' => 'Airplane', 'name_ru' => 'Самолет', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Airlines
        DB::table('airlines')->insert([
            ['name' => 'Centrum Air', 'code' => 'C2', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Turkish Airlines', 'code' => 'TK', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tour Types (required by tours.tour_type_id NOT NULL)
        DB::table('tour_types')->insert([
            ['name_en' => 'Standard', 'name_ru' => 'Стандарт', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Room Types
        DB::table('room_types')->insert([
            ['code' => 'SGL', 'name_en' => 'Single', 'name_ru' => 'Одноместный', 'max_adults' => 1, 'max_children' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'DBL', 'name_en' => 'Double', 'name_ru' => 'Двухместный', 'max_adults' => 2, 'max_children' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Roles (Spatie)
        \Spatie\Permission\Models\Role::create(['name' => 'administrator', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'manager', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'accountant', 'guard_name' => 'web']);

        // Settings
        DB::table('settings')->insert([
            ['key' => 'tour_hidden_fee', 'value' => '60', 'type' => 'number', 'group' => 'pricing', 'label' => 'Hidden Fee ($)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tour_agent_fee', 'value' => '50', 'type' => 'number', 'group' => 'pricing', 'label' => 'Agent Fee ($)', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Reference data seeded.');
    }
}
