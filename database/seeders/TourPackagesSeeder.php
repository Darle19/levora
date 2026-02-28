<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TourPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ─── NEW COUNTRIES ───
        $indonesiaId = DB::table('countries')->insertGetId([
            'name_en' => 'Indonesia', 'name_ru' => 'Индонезия', 'name_uz' => 'Indoneziya',
            'code' => 'IDN', 'is_active' => 1, 'order' => 7, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $georgiaId = DB::table('countries')->insertGetId([
            'name_en' => 'Georgia', 'name_ru' => 'Грузия', 'name_uz' => 'Gruziya',
            'code' => 'GEO', 'is_active' => 1, 'order' => 8, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $azerbaijanId = DB::table('countries')->insertGetId([
            'name_en' => 'Azerbaijan', 'name_ru' => 'Азербайджан', 'name_uz' => 'Ozarbayjon',
            'code' => 'AZE', 'is_active' => 1, 'order' => 9, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Existing country IDs
        $turkeyId = 2;
        $franceId = 6;
        $uzbekistanId = 1;

        // ─── NEW CITIES ───
        $denpasarId = DB::table('cities')->insertGetId([
            'name_en' => 'Denpasar', 'name_ru' => 'Денпасар', 'name_uz' => 'Denpasar',
            'country_id' => $indonesiaId, 'is_active' => 1, 'order' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $batumiCityId = DB::table('cities')->insertGetId([
            'name_en' => 'Batumi', 'name_ru' => 'Батуми', 'name_uz' => 'Batumi',
            'country_id' => $georgiaId, 'is_active' => 1, 'order' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $bakuCityId = DB::table('cities')->insertGetId([
            'name_en' => 'Baku', 'name_ru' => 'Баку', 'name_uz' => 'Boku',
            'country_id' => $azerbaijanId, 'is_active' => 1, 'order' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Existing city IDs
        $tashkentCityId = 1;
        $istanbulCityId = 2;
        $parisCityId = 6;

        // ─── NEW AIRPORTS ───
        $dpsId = DB::table('airports')->insertGetId([
            'name_en' => 'Ngurah Rai International Airport', 'name_ru' => 'Аэропорт Нгурах-Рай', 'name_uz' => 'Ngurah Rai xalqaro aeroporti',
            'code' => 'DPS', 'city_id' => $denpasarId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $busId = DB::table('airports')->insertGetId([
            'name_en' => 'Batumi International Airport', 'name_ru' => 'Аэропорт Батуми', 'name_uz' => 'Batumi xalqaro aeroporti',
            'code' => 'BUS', 'city_id' => $batumiCityId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $gydId = DB::table('airports')->insertGetId([
            'name_en' => 'Heydar Aliyev International Airport', 'name_ru' => 'Аэропорт Гейдар Алиев', 'name_uz' => 'Haydar Aliyev xalqaro aeroporti',
            'code' => 'GYD', 'city_id' => $bakuCityId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Existing airport IDs
        $tasId = 1; // Tashkent TAS
        $istId = 2; // Istanbul IST
        $cdgId = 4; // Paris CDG

        // ─── NEW AIRLINES ───
        $centrumAirId = DB::table('airlines')->insertGetId([
            'name' => 'Centrum Air', 'code' => 'C2', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $batikAirId = DB::table('airlines')->insertGetId([
            'name' => 'Batik Air', 'code' => 'ID', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Existing airline IDs
        $uzAirsId = 1;    // Uzbekistan Airways HY
        $turkishId = 2;   // Turkish Airlines TK

        // Existing IDs
        $usdId = 1;
        $cat5 = 1; // 5 stars
        $cat4 = 2; // 4 stars
        $cat3 = 3; // 3 stars
        $airplaneId = 1;
        $mealBB = 1; $mealHB = 2; $mealFB = 3; $mealAI = 4; $mealRO = 5;
        $tourTypeBeach = 1; $tourTypeExcursion = 2; $tourTypeCombined = 3;
        $programStandard = 1;

        // ─── RESORTS / DISTRICTS ───

        // Istanbul districts
        $sultanahmetId = DB::table('resorts')->insertGetId([
            'name_en' => 'Sultanahmet', 'name_ru' => 'Султанахмет', 'name_uz' => 'Sultonaxmet',
            'country_id' => $turkeyId, 'city_id' => $istanbulCityId, 'is_active' => 1, 'order' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $fatihId = DB::table('resorts')->insertGetId([
            'name_en' => 'Fatih', 'name_ru' => 'Фатих', 'name_uz' => 'Fotih',
            'country_id' => $turkeyId, 'city_id' => $istanbulCityId, 'is_active' => 1, 'order' => 2, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $beyogluId = DB::table('resorts')->insertGetId([
            'name_en' => 'Beyoglu', 'name_ru' => 'Бейоглу', 'name_uz' => 'Beyoglu',
            'country_id' => $turkeyId, 'city_id' => $istanbulCityId, 'is_active' => 1, 'order' => 3, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Baku district
        $bakuOldCityId = DB::table('resorts')->insertGetId([
            'name_en' => 'Old City (Icherisheher)', 'name_ru' => 'Старый Город (Ичеришехер)', 'name_uz' => 'Eski Shahar',
            'country_id' => $azerbaijanId, 'city_id' => $bakuCityId, 'is_active' => 1, 'order' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Bali districts
        $baliResorts = [];
        $baliDistrictNames = [
            ['Kuta', 'Кута', 'Kuta'],
            ['Seminyak', 'Семиньяк', 'Seminyak'],
            ['Nusa Dua', 'Нуса-Дуа', 'Nusa Dua'],
            ['Ubud', 'Убуд', 'Ubud'],
            ['Sanur', 'Санур', 'Sanur'],
            ['Canggu', 'Чангу', 'Changgu'],
            ['Jimbaran', 'Джимбаран', 'Jimbaran'],
            ['Legian', 'Легиан', 'Legian'],
        ];
        foreach ($baliDistrictNames as $i => $d) {
            $baliResorts[$d[0]] = DB::table('resorts')->insertGetId([
                'name_en' => $d[0], 'name_ru' => $d[1], 'name_uz' => $d[2],
                'country_id' => $indonesiaId, 'city_id' => $denpasarId, 'is_active' => 1, 'order' => $i + 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Batumi districts
        $batumiResorts = [];
        $batumiDistrictNames = [
            ['Batumi Boulevard', 'Батумский бульвар', 'Batumi Bulvari'],
            ['Batumi Old Town', 'Старый Батуми', 'Eski Batumi'],
            ['Gonio', 'Гонио', 'Gonio'],
            ['Kvariati', 'Квариати', 'Kvariati'],
        ];
        foreach ($batumiDistrictNames as $i => $d) {
            $batumiResorts[$d[0]] = DB::table('resorts')->insertGetId([
                'name_en' => $d[0], 'name_ru' => $d[1], 'name_uz' => $d[2],
                'country_id' => $georgiaId, 'city_id' => $batumiCityId, 'is_active' => 1, 'order' => $i + 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Paris districts
        $parisResorts = [];
        $parisDistrictNames = [
            ['Champs-Elysees', 'Шанз-Элизе', 'Shanz-Elize'],
            ['Montmartre', 'Монмартр', 'Monmartr'],
            ['Le Marais', 'Ле-Маре', 'Le Mare'],
            ['Saint-Germain-des-Pres', 'Сен-Жермен-де-Пре', 'Sen-Jermen'],
            ['Opera', 'Опера', 'Opera'],
            ['Bastille', 'Бастилия', 'Bastiliya'],
            ['Latin Quarter', 'Латинский квартал', 'Lotin kvartali'],
            ['Trocadero', 'Трокадеро', 'Trokadero'],
        ];
        foreach ($parisDistrictNames as $i => $d) {
            $parisResorts[$d[0]] = DB::table('resorts')->insertGetId([
                'name_en' => $d[0], 'name_ru' => $d[1], 'name_uz' => $d[2],
                'country_id' => $franceId, 'city_id' => $parisCityId, 'is_active' => 1, 'order' => $i + 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ─── HOTELS ───

        // Helper to insert hotel
        $insertHotel = function (string $name, int $resortId, int $catId, float $rating, float $pricePP) use ($now, $usdId) {
            return DB::table('hotels')->insertGetId([
                'name' => $name, 'description' => null, 'address' => null,
                'resort_id' => $resortId, 'hotel_category_id' => $catId,
                'rating' => $rating, 'images' => null, 'amenities' => null, 'is_active' => 1,
                'price_per_person' => $pricePP, 'currency_id' => $usdId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        };

        // --- Istanbul Sultanahmet (4 hotels) ---
        $istanbulHotels = [
            $insertHotel('Four Seasons Hotel Istanbul at Sultanahmet', $sultanahmetId, $cat5, 4.9, 180.00),
            $insertHotel('Sultanahmet Palace Hotel', $sultanahmetId, $cat4, 4.5, 95.00),
            $insertHotel('Ibrahim Pasha Hotel', $sultanahmetId, $cat4, 4.4, 85.00),
            $insertHotel('Hotel Amira Istanbul', $sultanahmetId, $cat3, 4.2, 55.00),
        ];

        // --- Baku (1 hotel) ---
        $bakuHotels = [
            $insertHotel('JW Marriott Absheron Baku', $bakuOldCityId, $cat5, 4.8, 130.00),
        ];

        // --- Bali (40 hotels) ---
        $baliHotelData = [
            'Kuta' => [
                ['Hard Rock Hotel Bali', $cat4, 4.5, 75.00],
                ['Kuta Paradiso Hotel', $cat4, 4.2, 65.00],
                ['Discovery Kartika Plaza', $cat5, 4.6, 110.00],
                ['Bali Dynasty Resort', $cat4, 4.3, 70.00],
                ['Kuta Seaview Boutique Resort', $cat3, 4.0, 40.00],
            ],
            'Seminyak' => [
                ['W Bali Seminyak', $cat5, 4.7, 160.00],
                ['The Legian Seminyak', $cat5, 4.8, 180.00],
                ['Alila Seminyak', $cat5, 4.7, 150.00],
                ['Double Six Luxury Hotel', $cat4, 4.5, 95.00],
                ['Courtyard by Marriott Seminyak', $cat4, 4.3, 80.00],
            ],
            'Nusa Dua' => [
                ['The Mulia Bali', $cat5, 4.9, 200.00],
                ['St. Regis Bali Resort', $cat5, 4.9, 220.00],
                ['Hilton Bali Resort', $cat5, 4.7, 140.00],
                ['Sofitel Bali Nusa Dua', $cat5, 4.7, 150.00],
                ['Grand Hyatt Bali', $cat5, 4.6, 130.00],
            ],
            'Ubud' => [
                ['Viceroy Bali', $cat5, 4.9, 250.00],
                ['Four Seasons Resort at Sayan', $cat5, 4.9, 280.00],
                ['Hanging Gardens of Bali', $cat5, 4.8, 200.00],
                ['COMO Uma Ubud', $cat5, 4.7, 160.00],
                ['Alila Ubud', $cat4, 4.6, 100.00],
            ],
            'Sanur' => [
                ['Fairmont Sanur Beach Bali', $cat5, 4.7, 140.00],
                ['Prama Sanur Beach Hotel', $cat4, 4.4, 75.00],
                ['Hyatt Regency Bali Sanur', $cat5, 4.6, 120.00],
                ['Mercure Resort Sanur', $cat4, 4.3, 65.00],
                ['Maya Sanur Resort & Spa', $cat5, 4.7, 135.00],
            ],
            'Canggu' => [
                ['Hotel Tugu Bali', $cat5, 4.8, 170.00],
                ['COMO Uma Canggu', $cat5, 4.7, 155.00],
                ['The Slow Canggu', $cat4, 4.5, 90.00],
                ['Ayana Bali Resort', $cat5, 4.8, 180.00],
                ['FRii Bali Echo Beach', $cat3, 4.1, 45.00],
            ],
            'Jimbaran' => [
                ['AYANA Resort Jimbaran', $cat5, 4.8, 190.00],
                ['Four Seasons Resort Jimbaran', $cat5, 4.9, 260.00],
                ['InterContinental Bali Resort', $cat5, 4.7, 150.00],
                ['Rimba Jimbaran Bali', $cat5, 4.6, 130.00],
                ['Belmond Jimbaran Puri', $cat5, 4.8, 210.00],
            ],
            'Legian' => [
                ['Padma Resort Legian', $cat5, 4.7, 120.00],
                ['All Seasons Resort Legian', $cat3, 4.0, 40.00],
                ['Jayakarta Hotel Legian', $cat4, 4.2, 60.00],
                ['Pullman Bali Legian', $cat5, 4.6, 110.00],
                ['Bali Mandira Beach Resort', $cat4, 4.4, 75.00],
            ],
        ];

        $baliHotels = [];
        foreach ($baliHotelData as $district => $hotels) {
            foreach ($hotels as $h) {
                $baliHotels[] = [
                    'id' => $insertHotel($h[0], $baliResorts[$district], $h[1], $h[2], $h[3]),
                    'resort_id' => $baliResorts[$district],
                ];
            }
        }

        // --- Batumi (20 hotels) ---
        $batumiHotelData = [
            'Batumi Boulevard' => [
                ['Hilton Batumi', $cat5, 4.7, 95.00],
                ['Radisson Blu Hotel Batumi', $cat5, 4.6, 85.00],
                ['Sheraton Batumi Hotel', $cat5, 4.6, 90.00],
                ['Wyndham Batumi', $cat4, 4.4, 60.00],
                ['Euphoria Batumi Hotel', $cat4, 4.3, 55.00],
            ],
            'Batumi Old Town' => [
                ['Piazza Boutique Hotel', $cat4, 4.5, 65.00],
                ['Colosseum Marina Hotel', $cat4, 4.3, 58.00],
                ['Grand Hotel Batumi', $cat3, 4.1, 40.00],
                ['Divan Suites Batumi', $cat4, 4.4, 62.00],
                ['Legacy Hotel Batumi', $cat3, 4.0, 38.00],
            ],
            'Gonio' => [
                ['Castello Mare Hotel & Wellness Resort', $cat5, 4.8, 110.00],
                ['Paragraph Resort & Spa', $cat5, 4.7, 100.00],
                ['Green Cape Hotel', $cat4, 4.4, 65.00],
                ['Gonio Palace Hotel', $cat3, 4.1, 42.00],
                ['Sunset Gonio Resort', $cat4, 4.3, 58.00],
            ],
            'Kvariati' => [
                ['Sunrise Hotel Kvariati', $cat3, 4.0, 35.00],
                ['Kvariati Beach Hotel', $cat3, 3.9, 32.00],
                ['Oasis Resort Kvariati', $cat4, 4.2, 50.00],
                ['Azure Beach Hotel Kvariati', $cat3, 4.0, 36.00],
                ['Sea Breeze Kvariati', $cat3, 3.8, 30.00],
            ],
        ];

        $batumiHotels = [];
        foreach ($batumiHotelData as $district => $hotels) {
            foreach ($hotels as $h) {
                $batumiHotels[] = [
                    'id' => $insertHotel($h[0], $batumiResorts[$district], $h[1], $h[2], $h[3]),
                    'resort_id' => $batumiResorts[$district],
                ];
            }
        }

        // --- Paris (40 hotels) ---
        $parisHotelData = [
            'Champs-Elysees' => [
                ['Le Royal Monceau Raffles Paris', $cat5, 4.9, 380.00],
                ['Hotel Plaza Athenee', $cat5, 4.9, 420.00],
                ['Four Seasons Hotel George V', $cat5, 4.9, 450.00],
                ['Le Bristol Paris', $cat5, 4.9, 400.00],
                ['Prince de Galles Paris', $cat5, 4.7, 320.00],
            ],
            'Montmartre' => [
                ['Terrass Hotel Montmartre', $cat4, 4.4, 150.00],
                ['Le Relais Montmartre', $cat3, 4.2, 90.00],
                ['Timhotel Montmartre', $cat3, 4.0, 80.00],
                ['Hotel des Arts Montmartre', $cat3, 4.1, 85.00],
                ['Maison Souquet', $cat5, 4.8, 280.00],
            ],
            'Le Marais' => [
                ['Hotel du Petit Moulin', $cat4, 4.5, 170.00],
                ['Pavillon de la Reine', $cat5, 4.8, 300.00],
                ['Les Bains Paris', $cat5, 4.7, 270.00],
                ['Hotel Jules & Jim', $cat4, 4.4, 155.00],
                ['Hotel de Jobo', $cat4, 4.5, 165.00],
            ],
            'Saint-Germain-des-Pres' => [
                ['Hotel Lutetia Paris', $cat5, 4.8, 350.00],
                ['Relais Christine', $cat5, 4.7, 290.00],
                ['Hotel Bel Ami', $cat4, 4.5, 180.00],
                ["L'Hotel Paris", $cat5, 4.8, 310.00],
                ["Hotel d'Aubusson", $cat5, 4.7, 280.00],
            ],
            'Opera' => [
                ['InterContinental Paris Le Grand', $cat5, 4.7, 300.00],
                ['W Paris Opera', $cat5, 4.6, 270.00],
                ['Hotel Edouard 7', $cat4, 4.4, 160.00],
                ['Grand Hotel du Palais Royal', $cat5, 4.8, 330.00],
                ['Hotel Banke Paris', $cat5, 4.6, 250.00],
            ],
            'Bastille' => [
                ['Paris Bastille Boutet MGallery', $cat5, 4.6, 220.00],
                ['Hotel Bastille de Launay', $cat3, 4.1, 95.00],
                ['Hotel Lyon Bastille', $cat3, 4.0, 85.00],
                ['Hotel Les Jardins du Marais', $cat4, 4.3, 145.00],
                ['Hotel de la Porte Doree', $cat3, 4.1, 90.00],
            ],
            'Latin Quarter' => [
                ['Hotel Monge Paris', $cat4, 4.5, 165.00],
                ['Hotel des Grandes Ecoles', $cat3, 4.3, 100.00],
                ['Hotel Le Lapin Blanc', $cat4, 4.4, 150.00],
                ['Select Hotel Paris', $cat3, 4.1, 88.00],
                ['Hotel Residence Henri IV', $cat4, 4.5, 160.00],
            ],
            'Trocadero' => [
                ['Shangri-La Hotel Paris', $cat5, 4.9, 450.00],
                ['The Peninsula Paris', $cat5, 4.9, 480.00],
                ['Hotel Raphael Paris', $cat5, 4.7, 320.00],
                ['Hotel Duret Paris', $cat4, 4.4, 170.00],
                ['Citadines Trocadero Paris', $cat3, 4.0, 95.00],
            ],
        ];

        $parisHotels = [];
        foreach ($parisHotelData as $district => $hotels) {
            foreach ($hotels as $h) {
                $parisHotels[] = [
                    'id' => $insertHotel($h[0], $parisResorts[$district], $h[1], $h[2], $h[3]),
                    'resort_id' => $parisResorts[$district],
                ];
            }
        }

        // ─── FLIGHTS ───
        // Multiple departure dates: weekly from March to June 2026
        $departureDates = [];
        $date = now()->parse('2026-03-01');
        while ($date->lt(now()->parse('2026-07-01'))) {
            $departureDates[] = $date->format('Y-m-d');
            $date->addDays(7);
        }

        $insertFlight = function (int $airlineId, int $fromAirportId, int $toAirportId, string $flightNum, string $depDate, string $depTime, string $arrTime, string $arrDate, float $priceAdult, float $priceChild, int $seats) use ($now, $usdId) {
            return DB::table('flights')->insertGetId([
                'airline_id' => $airlineId,
                'from_airport_id' => $fromAirportId,
                'to_airport_id' => $toAirportId,
                'flight_number' => $flightNum,
                'departure_date' => $depDate,
                'departure_time' => $depTime,
                'arrival_time' => $arrTime,
                'arrival_date' => $arrDate,
                'price_adult' => $priceAdult,
                'price_child' => $priceChild,
                'price_infant' => 0,
                'currency_id' => $usdId,
                'available_seats' => $seats,
                'class_type' => 'economy',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        // Flight storage: [depDate => ['outbound' => id, 'return' => id, ...]]
        $turkeyFlights = [];
        $bakuFlightsFromIst = [];
        $bakuFlightsReturn = [];
        $baliFlights = [];
        $batumiFlights = [];
        $parisFlightsTK = [];
        $parisFlightsHY = [];

        $flightCounter = ['C2' => 100, 'ID' => 200, 'TK' => 300, 'HY' => 400];

        foreach ($departureDates as $depDate) {
            $returnDate7 = now()->parse($depDate)->addDays(7)->format('Y-m-d');
            $returnDate10 = now()->parse($depDate)->addDays(10)->format('Y-m-d');

            // 1. Turkey Combi: TAS → IST (Centrum Air)
            $fNum = 'C2' . $flightCounter['C2']++;
            $outId = $insertFlight($centrumAirId, $tasId, $istId, $fNum, $depDate, '08:00', '11:30', $depDate, 250.00, 180.00, 180);
            // IST → TAS return after 10 nights (combi)
            $fNum = 'C2' . $flightCounter['C2']++;
            $retId = $insertFlight($centrumAirId, $istId, $tasId, $fNum, $returnDate10, '14:00', '19:30', $returnDate10, 250.00, 180.00, 180);
            $turkeyFlights[$depDate] = ['outbound' => $outId, 'return' => $retId];

            // IST → GYD connecting (after 5 nights in Istanbul)
            $istBakuDate = now()->parse($depDate)->addDays(5)->format('Y-m-d');
            $fNum = 'C2' . $flightCounter['C2']++;
            $istGydId = $insertFlight($centrumAirId, $istId, $gydId, $fNum, $istBakuDate, '10:00', '13:00', $istBakuDate, 150.00, 110.00, 180);
            // GYD → TAS return
            $fNum = 'C2' . $flightCounter['C2']++;
            $gydTasId = $insertFlight($centrumAirId, $gydId, $tasId, $fNum, $returnDate10, '16:00', '19:00', $returnDate10, 200.00, 150.00, 180);
            $bakuFlightsFromIst[$depDate] = ['outbound' => $istGydId, 'return' => $gydTasId];

            // 2. Bali: TAS → DPS (Batik Air)
            $fNum = 'ID' . $flightCounter['ID']++;
            $outId = $insertFlight($batikAirId, $tasId, $dpsId, $fNum, $depDate, '22:00', '12:00', now()->parse($depDate)->addDay()->format('Y-m-d'), 450.00, 340.00, 220);
            $fNum = 'ID' . $flightCounter['ID']++;
            $retId = $insertFlight($batikAirId, $dpsId, $tasId, $fNum, $returnDate10, '14:00', '22:00', $returnDate10, 450.00, 340.00, 220);
            $baliFlights[$depDate] = ['outbound' => $outId, 'return' => $retId];

            // 3. Batumi: TAS → BUS (Centrum)
            $fNum = 'C2' . $flightCounter['C2']++;
            $outId = $insertFlight($centrumAirId, $tasId, $busId, $fNum, $depDate, '09:00', '11:30', $depDate, 200.00, 150.00, 150);
            $fNum = 'C2' . $flightCounter['C2']++;
            $retId = $insertFlight($centrumAirId, $busId, $tasId, $fNum, $returnDate7, '13:00', '15:30', $returnDate7, 200.00, 150.00, 150);
            $batumiFlights[$depDate] = ['outbound' => $outId, 'return' => $retId];

            // 4. Paris: TAS → CDG (Turkish via IST) outbound
            $fNum = 'TK' . $flightCounter['TK']++;
            $outId = $insertFlight($turkishId, $tasId, $cdgId, $fNum, $depDate, '06:00', '14:30', $depDate, 400.00, 300.00, 200);
            $parisFlightsTK[$depDate] = ['outbound' => $outId];

            // Paris: CDG → TAS (UzAirs) return
            $fNum = 'HY' . $flightCounter['HY']++;
            $retId = $insertFlight($uzAirsId, $cdgId, $tasId, $fNum, $returnDate7, '16:00', '02:00', now()->parse($returnDate7)->addDay()->format('Y-m-d'), 380.00, 285.00, 200);
            $parisFlightsHY[$depDate] = ['return' => $retId];
        }

        // ─── TOURS ───
        $markup = (float) Setting::getValue('tour_markup_percent', 15);
        $tourIds = [];

        // Helper to create a tour
        $createTour = function (array $data, array $flightIds) use ($now, &$tourIds, $markup) {
            $hotelPrice = $data['hotel_price'] ?? 0;
            $flightCost = $data['flight_cost'] ?? 0;
            $nights = $data['nights'];
            $baseCost = ($hotelPrice * $nights) + $flightCost;
            $finalPrice = round($baseCost * (1 + $markup / 100), 2);

            $tourId = DB::table('tours')->insertGetId([
                'tour_type_id' => $data['tour_type_id'],
                'program_type_id' => $data['program_type_id'],
                'country_id' => $data['country_id'],
                'resort_id' => $data['resort_id'],
                'hotel_id' => $data['hotel_id'],
                'transport_type_id' => $data['transport_type_id'],
                'departure_city_id' => $data['departure_city_id'],
                'nights' => $nights,
                'price' => $finalPrice,
                'currency_id' => $data['currency_id'],
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'adults' => $data['adults'] ?? 2,
                'children' => $data['children'] ?? 0,
                'meal_type_id' => $data['meal_type_id'],
                'is_available' => 1,
                'is_hot' => $data['is_hot'] ?? 0,
                'instant_confirmation' => 0,
                'no_stop_sale' => 1,
                'child_bed_separate' => 0,
                'comfortable_seats' => 0,
                'markup_percent' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Link flights via pivot
            foreach ($flightIds as $fId) {
                DB::table('tour_flight')->insert([
                    'tour_id' => $tourId,
                    'flight_id' => $fId['id'],
                    'direction' => $fId['direction'],
                ]);
            }

            $tourIds[] = $tourId;
            return $tourId;
        };

        // Get hotel prices by ID
        $getHotelPrice = function (int $hotelId): float {
            return (float) DB::table('hotels')->where('id', $hotelId)->value('price_per_person');
        };

        // ═══════════════════════════════════════════════
        // TOUR PACKAGE 1: Combi Turkey Istanbul + Baku
        // ═══════════════════════════════════════════════
        foreach ($departureDates as $idx => $depDate) {
            $returnDate = now()->parse($depDate)->addDays(10)->format('Y-m-d');

            // Istanbul hotels (4 tours per departure date)
            foreach ($istanbulHotels as $hotelId) {
                $flightCost = 250 + 150; // outbound TAS→IST + IST→GYD connecting
                $createTour([
                    'tour_type_id' => $tourTypeCombined,
                    'program_type_id' => $programStandard,
                    'country_id' => $turkeyId,
                    'resort_id' => $sultanahmetId,
                    'hotel_id' => $hotelId,
                    'transport_type_id' => $airplaneId,
                    'departure_city_id' => $tashkentCityId,
                    'nights' => 10,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate,
                    'meal_type_id' => $mealBB,
                    'is_hot' => $idx < 3 ? 1 : 0,
                    'hotel_price' => $getHotelPrice($hotelId),
                    'flight_cost' => $flightCost,
                ], [
                    ['id' => $turkeyFlights[$depDate]['outbound'], 'direction' => 'outbound'],
                    ['id' => $turkeyFlights[$depDate]['return'], 'direction' => 'return'],
                    ['id' => $bakuFlightsFromIst[$depDate]['outbound'], 'direction' => 'outbound'],
                ]);
            }

            // Baku hotel (1 tour per departure date)
            foreach ($bakuHotels as $hotelId) {
                $flightCost = 250 + 150 + 200; // TAS→IST + IST→GYD + GYD→TAS
                $createTour([
                    'tour_type_id' => $tourTypeCombined,
                    'program_type_id' => $programStandard,
                    'country_id' => $azerbaijanId,
                    'resort_id' => $bakuOldCityId,
                    'hotel_id' => $hotelId,
                    'transport_type_id' => $airplaneId,
                    'departure_city_id' => $tashkentCityId,
                    'nights' => 10,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate,
                    'meal_type_id' => $mealBB,
                    'is_hot' => $idx < 3 ? 1 : 0,
                    'hotel_price' => $getHotelPrice($hotelId),
                    'flight_cost' => $flightCost,
                ], [
                    ['id' => $turkeyFlights[$depDate]['outbound'], 'direction' => 'outbound'],
                    ['id' => $bakuFlightsFromIst[$depDate]['outbound'], 'direction' => 'outbound'],
                    ['id' => $bakuFlightsFromIst[$depDate]['return'], 'direction' => 'return'],
                ]);
            }
        }

        // ═══════════════════════════════════════════════
        // TOUR PACKAGE 2: Bali Indonesia
        // ═══════════════════════════════════════════════
        foreach ($departureDates as $idx => $depDate) {
            $returnDate = now()->parse($depDate)->addDays(10)->format('Y-m-d');

            foreach ($baliHotels as $hotel) {
                $flightCost = 450 + 450; // outbound + return
                $createTour([
                    'tour_type_id' => $tourTypeBeach,
                    'program_type_id' => $programStandard,
                    'country_id' => $indonesiaId,
                    'resort_id' => $hotel['resort_id'],
                    'hotel_id' => $hotel['id'],
                    'transport_type_id' => $airplaneId,
                    'departure_city_id' => $tashkentCityId,
                    'nights' => 10,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate,
                    'meal_type_id' => $mealBB,
                    'is_hot' => $idx < 2 ? 1 : 0,
                    'hotel_price' => $getHotelPrice($hotel['id']),
                    'flight_cost' => $flightCost,
                ], [
                    ['id' => $baliFlights[$depDate]['outbound'], 'direction' => 'outbound'],
                    ['id' => $baliFlights[$depDate]['return'], 'direction' => 'return'],
                ]);
            }
        }

        // ═══════════════════════════════════════════════
        // TOUR PACKAGE 3: Georgia Batumi
        // ═══════════════════════════════════════════════
        foreach ($departureDates as $idx => $depDate) {
            $returnDate = now()->parse($depDate)->addDays(7)->format('Y-m-d');

            foreach ($batumiHotels as $hotel) {
                $flightCost = 200 + 200; // outbound + return
                $createTour([
                    'tour_type_id' => $tourTypeBeach,
                    'program_type_id' => $programStandard,
                    'country_id' => $georgiaId,
                    'resort_id' => $hotel['resort_id'],
                    'hotel_id' => $hotel['id'],
                    'transport_type_id' => $airplaneId,
                    'departure_city_id' => $tashkentCityId,
                    'nights' => 7,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate,
                    'meal_type_id' => $mealHB,
                    'is_hot' => $idx < 2 ? 1 : 0,
                    'hotel_price' => $getHotelPrice($hotel['id']),
                    'flight_cost' => $flightCost,
                ], [
                    ['id' => $batumiFlights[$depDate]['outbound'], 'direction' => 'outbound'],
                    ['id' => $batumiFlights[$depDate]['return'], 'direction' => 'return'],
                ]);
            }
        }

        // ═══════════════════════════════════════════════
        // TOUR PACKAGE 4: France Paris
        // ═══════════════════════════════════════════════
        foreach ($departureDates as $idx => $depDate) {
            $returnDate = now()->parse($depDate)->addDays(7)->format('Y-m-d');

            foreach ($parisHotels as $hotel) {
                $flightCost = 400 + 380; // TK outbound + HY return
                $createTour([
                    'tour_type_id' => $tourTypeExcursion,
                    'program_type_id' => $programStandard,
                    'country_id' => $franceId,
                    'resort_id' => $hotel['resort_id'],
                    'hotel_id' => $hotel['id'],
                    'transport_type_id' => $airplaneId,
                    'departure_city_id' => $tashkentCityId,
                    'nights' => 7,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate,
                    'meal_type_id' => $mealBB,
                    'is_hot' => $idx < 2 ? 1 : 0,
                    'hotel_price' => $getHotelPrice($hotel['id']),
                    'flight_cost' => $flightCost,
                ], [
                    ['id' => $parisFlightsTK[$depDate]['outbound'], 'direction' => 'outbound'],
                    ['id' => $parisFlightsHY[$depDate]['return'], 'direction' => 'return'],
                ]);
            }
        }

        $this->command->info('Created ' . count($tourIds) . ' tours across 4 packages.');
        $this->command->info('Hotels: Istanbul=' . count($istanbulHotels) . ', Baku=' . count($bakuHotels) . ', Bali=' . count($baliHotels) . ', Batumi=' . count($batumiHotels) . ', Paris=' . count($parisHotels));
    }
}
