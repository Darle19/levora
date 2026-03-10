<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BasicDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Currencies
        $usd = \App\Models\Currency::create([
            'code' => 'USD',
            'name_en' => 'US Dollar',
            'name_ru' => 'Доллар США',
            'name_uz' => 'AQSh dollari',
            'symbol' => '$',
            'is_active' => true
        ]);

        $uzs = \App\Models\Currency::create([
            'code' => 'UZS',
            'name_en' => 'Uzbek Som',
            'name_ru' => 'Узбекский сум',
            'name_uz' => 'O\'zbek so\'mi',
            'symbol' => 'сўм',
            'is_active' => true
        ]);

        $eur = \App\Models\Currency::create([
            'code' => 'EUR',
            'name_en' => 'Euro',
            'name_ru' => 'Евро',
            'name_uz' => 'Yevro',
            'symbol' => '€',
            'is_active' => true
        ]);

        // Countries
        $uzbekistan = \App\Models\Country::create(['name_en' => 'Uzbekistan', 'name_ru' => 'Узбекистан', 'name_uz' => 'O\'zbekiston', 'code' => 'UZB', 'is_active' => true, 'order' => 1]);
        $turkey = \App\Models\Country::create(['name_en' => 'Turkey', 'name_ru' => 'Турция', 'name_uz' => 'Turkiya', 'code' => 'TUR', 'is_active' => true, 'order' => 2]);
        $uae = \App\Models\Country::create(['name_en' => 'UAE', 'name_ru' => 'ОАЭ', 'name_uz' => 'BAA', 'code' => 'ARE', 'is_active' => true, 'order' => 3]);
        $egypt = \App\Models\Country::create(['name_en' => 'Egypt', 'name_ru' => 'Египет', 'name_uz' => 'Misr', 'code' => 'EGY', 'is_active' => true, 'order' => 4]);
        $thailand = \App\Models\Country::create(['name_en' => 'Thailand', 'name_ru' => 'Таиланд', 'name_uz' => 'Tailand', 'code' => 'THA', 'is_active' => true, 'order' => 5]);

        // Cities
        $tashkent = \App\Models\City::create(['country_id' => $uzbekistan->id, 'name_en' => 'Tashkent', 'name_ru' => 'Ташкент', 'name_uz' => 'Toshkent', 'is_active' => true]);
        $istanbul = \App\Models\City::create(['country_id' => $turkey->id, 'name_en' => 'Istanbul', 'name_ru' => 'Стамбул', 'name_uz' => 'Istanbul', 'is_active' => true]);
        $dubai = \App\Models\City::create(['country_id' => $uae->id, 'name_en' => 'Dubai', 'name_ru' => 'Дубай', 'name_uz' => 'Dubay', 'is_active' => true]);
        $cairo = \App\Models\City::create(['country_id' => $egypt->id, 'name_en' => 'Cairo', 'name_ru' => 'Каир', 'name_uz' => 'Qohira', 'is_active' => true]);
        $bangkok = \App\Models\City::create(['country_id' => $thailand->id, 'name_en' => 'Bangkok', 'name_ru' => 'Бангкок', 'name_uz' => 'Bangkok', 'is_active' => true]);
        $samarkand = \App\Models\City::create(['country_id' => $uzbekistan->id, 'name_en' => 'Samarkand', 'name_ru' => 'Самарканд', 'name_uz' => 'Samarqand', 'is_active' => true]);
        $bukhara = \App\Models\City::create(['country_id' => $uzbekistan->id, 'name_en' => 'Bukhara', 'name_ru' => 'Бухара', 'name_uz' => 'Buxoro', 'is_active' => true]);

        // Resorts
        $antalya = \App\Models\Resort::create(['country_id' => $turkey->id, 'name_en' => 'Antalya', 'name_ru' => 'Анталия', 'name_uz' => 'Antalya', 'is_active' => true]);
        $bodrum = \App\Models\Resort::create(['country_id' => $turkey->id, 'name_en' => 'Bodrum', 'name_ru' => 'Бодрум', 'name_uz' => 'Bodrum', 'is_active' => true]);
        $phuket = \App\Models\Resort::create(['country_id' => $thailand->id, 'name_en' => 'Phuket', 'name_ru' => 'Пхукет', 'name_uz' => 'Phuket', 'is_active' => true]);
        $sharm = \App\Models\Resort::create(['country_id' => $egypt->id, 'name_en' => 'Sharm El-Sheikh', 'name_ru' => 'Шарм-эль-Шейх', 'name_uz' => 'Sharm El-Shayx', 'is_active' => true]);

        // Airports
        \App\Models\Airport::create(['city_id' => $tashkent->id, 'name_en' => 'Tashkent International Airport', 'name_ru' => 'Международный аэропорт Ташкента', 'name_uz' => 'Toshkent xalqaro aeroporti', 'code' => 'TAS', 'is_active' => true]);
        \App\Models\Airport::create(['city_id' => $istanbul->id, 'name_en' => 'Istanbul Airport', 'name_ru' => 'Аэропорт Стамбул', 'name_uz' => 'Istanbul aeroporti', 'code' => 'IST', 'is_active' => true]);
        \App\Models\Airport::create(['city_id' => $dubai->id, 'name_en' => 'Dubai International Airport', 'name_ru' => 'Международный аэропорт Дубая', 'name_uz' => 'Dubay xalqaro aeroporti', 'code' => 'DXB', 'is_active' => true]);
        \App\Models\Airport::create(['city_id' => $samarkand->id, 'name_en' => 'Samarkand International Airport', 'name_ru' => 'Международный аэропорт Самарканда', 'name_uz' => 'Samarqand xalqaro aeroporti', 'code' => 'SKD', 'is_active' => true]);
        \App\Models\Airport::create(['city_id' => $bukhara->id, 'name_en' => 'Bukhara International Airport', 'name_ru' => 'Международный аэропорт Бухары', 'name_uz' => 'Buxoro xalqaro aeroporti', 'code' => 'BHK', 'is_active' => true]);

        // Hotel Categories
        $cat5 = \App\Models\HotelCategory::create(['name' => '5 stars', 'stars' => 5, 'is_active' => true]);
        $cat4 = \App\Models\HotelCategory::create(['name' => '4 stars', 'stars' => 4, 'is_active' => true]);
        $cat3 = \App\Models\HotelCategory::create(['name' => '3 stars', 'stars' => 3, 'is_active' => true]);

        // Meal Types
        $bb = \App\Models\MealType::create(['code' => 'BB', 'name_en' => 'Bed & Breakfast', 'name_ru' => 'Завтрак', 'name_uz' => 'Nonushta', 'is_active' => true]);
        $hb = \App\Models\MealType::create(['code' => 'HB', 'name_en' => 'Half Board', 'name_ru' => 'Полупансион', 'name_uz' => 'Yarim pansion', 'is_active' => true]);
        $fb = \App\Models\MealType::create(['code' => 'FB', 'name_en' => 'Full Board', 'name_ru' => 'Полный пансион', 'name_uz' => 'To\'liq pansion', 'is_active' => true]);
        $ai = \App\Models\MealType::create(['code' => 'AI', 'name_en' => 'All Inclusive', 'name_ru' => 'Все включено', 'name_uz' => 'Hammasi kiritilgan', 'is_active' => true]);

        // Hotels
        $hotel1 = \App\Models\Hotel::create(['resort_id' => $antalya->id, 'hotel_category_id' => $cat5->id, 'name' => 'Grand Paradise Hotel', 'description' => 'Luxury 5-star hotel in Antalya', 'address' => 'Antalya, Turkey', 'rating' => 4.8, 'is_active' => true]);
        $hotel2 = \App\Models\Hotel::create(['resort_id' => $antalya->id, 'hotel_category_id' => $cat4->id, 'name' => 'Sunset Beach Resort', 'description' => 'Beautiful 4-star resort', 'address' => 'Antalya, Turkey', 'rating' => 4.5, 'is_active' => true]);
        $hotel3 = \App\Models\Hotel::create(['resort_id' => $phuket->id, 'hotel_category_id' => $cat5->id, 'name' => 'Phuket Paradise Resort', 'description' => 'Luxury resort in Phuket', 'address' => 'Phuket, Thailand', 'rating' => 4.9, 'is_active' => true]);

        // Tour Types
        $beach = \App\Models\TourType::create(['name_en' => 'Beach Holiday', 'name_ru' => 'Пляжный отдых', 'name_uz' => 'Plyaj dam olish', 'is_active' => true]);
        $excursion = \App\Models\TourType::create(['name_en' => 'Excursion', 'name_ru' => 'Экскурсионный', 'name_uz' => 'Ekskursion', 'is_active' => true]);
        $combined = \App\Models\TourType::create(['name_en' => 'Combined', 'name_ru' => 'Комбинированный', 'name_uz' => 'Kombinatsiyalashgan', 'is_active' => true]);

        // Program Types
        $standard = \App\Models\ProgramType::create(['name_en' => 'Standard', 'name_ru' => 'Стандарт', 'name_uz' => 'Standart', 'is_active' => true]);
        $vip = \App\Models\ProgramType::create(['name_en' => 'VIP', 'name_ru' => 'VIP', 'name_uz' => 'VIP', 'is_active' => true]);

        // Transport Types
        $plane = \App\Models\TransportType::create(['name_en' => 'Airplane', 'name_ru' => 'Самолет', 'name_uz' => 'Samolyot', 'is_active' => true]);
        $bus = \App\Models\TransportType::create(['name_en' => 'Bus', 'name_ru' => 'Автобус', 'name_uz' => 'Avtobus', 'is_active' => true]);

        // Airlines
        $uz_airways = \App\Models\Airline::create(['name' => 'Uzbekistan Airways', 'code' => 'HY', 'is_active' => true]);
        $turkish_airlines = \App\Models\Airline::create(['name' => 'Turkish Airlines', 'code' => 'TK', 'is_active' => true]);

        // Sample Tours - distributed across departure cities
        $departureCities = [$tashkent, $samarkand, $bukhara];
        for ($i = 1; $i <= 10; $i++) {
            $departureCity = $departureCities[$i % count($departureCities)];
            \App\Models\Tour::create([
                'tour_type_id' => $beach->id,
                'program_type_id' => $standard->id,
                'country_id' => $turkey->id,
                'resort_id' => $antalya->id,
                'hotel_id' => $hotel1->id,
                'transport_type_id' => $plane->id,
                'departure_city_id' => $departureCity->id,
                'nights' => 7,
                'price' => 1200 + ($i * 100),
                'currency_id' => $usd->id,
                'date_from' => now()->addDays($i),
                'date_to' => now()->addMonths(3),
                'adults' => 2,
                'children' => 0,
                'meal_type_id' => $ai->id,
                'is_available' => true,
                'is_hot' => $i <= 3,
                'instant_confirmation' => true,
                'no_stop_sale' => true
            ]);
        }

        // Room Types
        \App\Models\RoomType::create(['code' => 'SGL', 'name_en' => 'Single', 'name_ru' => 'Одноместный', 'name_uz' => 'Bir kishilik', 'max_adults' => 1, 'max_children' => 0, 'is_active' => true]);
        \App\Models\RoomType::create(['code' => 'DBL', 'name_en' => 'Double', 'name_ru' => 'Двухместный', 'name_uz' => 'Ikki kishilik', 'max_adults' => 2, 'max_children' => 1, 'is_active' => true]);
        \App\Models\RoomType::create(['code' => 'TRP', 'name_en' => 'Triple', 'name_ru' => 'Трёхместный', 'name_uz' => 'Uch kishilik', 'max_adults' => 3, 'max_children' => 1, 'is_active' => true]);
        \App\Models\RoomType::create(['code' => 'QDPL', 'name_en' => 'Quadruple', 'name_ru' => 'Четырёхместный', 'name_uz' => 'To\'rt kishilik', 'max_adults' => 4, 'max_children' => 2, 'is_active' => true]);

        // Room Only meal type
        \App\Models\MealType::create(['code' => 'RO', 'name_en' => 'Room Only', 'name_ru' => 'Без питания', 'name_uz' => 'Faqat xona', 'is_active' => true]);

        // Hotel Amenity Types
        \App\Models\HotelAmenityType::create(['name_en' => 'Beach', 'name_ru' => 'Пляж', 'name_uz' => 'Plyaj', 'icon' => 'beach', 'is_active' => true]);
        \App\Models\HotelAmenityType::create(['name_en' => 'Pool', 'name_ru' => 'Бассейн', 'name_uz' => 'Basseyn', 'icon' => 'pool', 'is_active' => true]);
        \App\Models\HotelAmenityType::create(['name_en' => 'Spa', 'name_ru' => 'Спа', 'name_uz' => 'Spa', 'icon' => 'spa', 'is_active' => true]);
        \App\Models\HotelAmenityType::create(['name_en' => 'Adults Only', 'name_ru' => 'Только взрослые', 'name_uz' => 'Faqat kattalar', 'icon' => 'adults_only', 'is_active' => true]);
        \App\Models\HotelAmenityType::create(['name_en' => 'Aqua Park', 'name_ru' => 'Аквапарк', 'name_uz' => 'Akvapark', 'icon' => 'aqua_park', 'is_active' => true]);
        \App\Models\HotelAmenityType::create(['name_en' => 'Gym', 'name_ru' => 'Фитнес', 'name_uz' => 'Sport zali', 'icon' => 'gym', 'is_active' => true]);

        // Paris airports (for Tashkent -> France dummy flight)
        $paris = \App\Models\City::create(['country_id' => \App\Models\Country::create(['name_en' => 'France', 'name_ru' => 'Франция', 'name_uz' => 'Frantsiya', 'code' => 'FRA', 'is_active' => true, 'order' => 6])->id, 'name_en' => 'Paris', 'name_ru' => 'Париж', 'name_uz' => 'Parij', 'is_active' => true]);
        $cdg = \App\Models\Airport::create(['city_id' => $paris->id, 'name_en' => 'Charles de Gaulle Airport', 'name_ru' => 'Аэропорт Шарль де Голль', 'name_uz' => 'Sharl de Goll aeroporti', 'code' => 'CDG', 'is_active' => true]);

        // Sample Flights (updated schema)
        \App\Models\Flight::create([
            'airline_id' => $uz_airways->id,
            'from_airport_id' => 1, // TAS
            'to_airport_id' => 2, // IST
            'flight_number' => 'HY123',
            'departure_time' => '10:00',
            'arrival_time' => '14:30',
            'price_adult' => 450,
            'price_child' => 350,
            'price_infant' => 50,
            'currency_id' => $usd->id,
            'available_seats' => 150,
            'departure_date' => now()->addDays(5),
            'arrival_date' => now()->addDays(5),
            'class_type' => 'economy',
            'is_active' => true,
        ]);

        // Tashkent -> Paris dummy flight
        \App\Models\Flight::create([
            'airline_id' => $uz_airways->id,
            'from_airport_id' => 1, // TAS
            'to_airport_id' => $cdg->id,
            'flight_number' => 'HY271',
            'departure_time' => '08:00',
            'arrival_time' => '12:30',
            'price_adult' => 650,
            'price_child' => 500,
            'price_infant' => 80,
            'currency_id' => $usd->id,
            'available_seats' => 180,
            'departure_date' => now()->addDays(7),
            'arrival_date' => now()->addDays(7),
            'class_type' => 'economy',
            'is_active' => true,
        ]);

        // Roles
        \Spatie\Permission\Models\Role::create(['name' => 'administrator', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'manager', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'accountant', 'guard_name' => 'web']);

        $this->command->info('Basic data seeded successfully!');
    }
}
