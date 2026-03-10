<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddHotelsFixFlightsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ─── EXISTING IDS ───
        $turkeyId = 2;
        $azerbaijanId = DB::table('countries')->where('code', 'AZE')->value('id');
        $indonesiaId = DB::table('countries')->where('code', 'IDN')->value('id');
        $georgiaId = DB::table('countries')->where('code', 'GEO')->value('id');
        $franceId = 6;
        $usdId = 1;
        $cat5 = 1; $cat4 = 2; $cat3 = 3;
        $airplaneId = 1;
        $mealBB = 1; $mealHB = 2;
        $tourTypeBeach = 1; $tourTypeExcursion = 2; $tourTypeCombined = 3;
        $programStandard = 1;
        $centrumAirId = DB::table('airlines')->where('code', 'C2')->value('id');

        $tasAirportId = 1;
        $istAirportId = 2;
        $gydAirportId = DB::table('airports')->where('code', 'GYD')->value('id');

        $sultanahmetId = DB::table('resorts')->where('name_en', 'Sultanahmet')->value('id');
        $fatihId = DB::table('resorts')->where('name_en', 'Fatih')->value('id');
        $beyogluId = DB::table('resorts')->where('name_en', 'Beyoglu')->value('id');
        $bakuOldCityId = DB::table('resorts')->where('name_en', 'Old City (Icherisheher)')->value('id');

        $istanbulCityId = 2;
        $bakuCityId = DB::table('cities')->where('name_en', 'Baku')->value('id');
        $tashkentCityId = 1;
        $samarkandCityId = DB::table('cities')->where('name_en', 'Samarkand')->value('id');
        $bukharaCityId = DB::table('cities')->where('name_en', 'Bukhara')->value('id');
        $skdAirportId = DB::table('airports')->where('code', 'SKD')->value('id');
        $bhkAirportId = DB::table('airports')->where('code', 'BHK')->value('id');

        // ═══════════════════════════════════════════════
        // STEP 1: FIX FLIGHTS - All must be TAS→X or X→TAS
        // ═══════════════════════════════════════════════

        // Convert IST→GYD flights to TAS→GYD
        $istGydFlights = DB::table('flights')
            ->where('from_airport_id', $istAirportId)
            ->where('to_airport_id', $gydAirportId)
            ->pluck('id');

        foreach ($istGydFlights as $flightId) {
            DB::table('flights')->where('id', $flightId)->update([
                'from_airport_id' => $tasAirportId,
                'departure_time' => '07:00',
                'arrival_time' => '10:00',
                'price_adult' => 220.00,
                'price_child' => 165.00,
                'updated_at' => $now,
            ]);
        }

        // Remove IST→GYD flight links from Turkey tours (country_id = Turkey)
        // These connecting flights shouldn't be on Istanbul hotel tours
        DB::table('tour_flight')
            ->whereIn('flight_id', $istGydFlights)
            ->whereIn('tour_id', function ($query) use ($turkeyId) {
                $query->select('id')->from('tours')->where('country_id', $turkeyId);
            })
            ->delete();

        // Update Baku tour prices (flight cost changed: was IST→GYD 150, now TAS→GYD 220)
        // Also remove the TAS→IST outbound from Baku tours (Baku tours shouldn't go via Istanbul)
        $bakuTourIds = DB::table('tours')->where('country_id', $azerbaijanId)->pluck('id');

        // Remove TAS→IST flights from Baku tours
        $tasIstFlights = DB::table('flights')
            ->where('from_airport_id', $tasAirportId)
            ->where('to_airport_id', $istAirportId)
            ->where('airline_id', $centrumAirId)
            ->pluck('id');

        DB::table('tour_flight')
            ->whereIn('flight_id', $tasIstFlights)
            ->whereIn('tour_id', $bakuTourIds)
            ->delete();

        // Update Istanbul tour prices (remove IST→GYD cost from their calculation)
        // Istanbul tours: TAS→IST ($250) + IST→TAS ($250) = $500 flight cost
        $markup = (float) (Setting::getValue('tour_markup_percent', 15) ?? 15);
        $istanbulTourIds = DB::table('tours')
            ->where('country_id', $turkeyId)
            ->where('tour_type_id', $tourTypeCombined)
            ->get(['id', 'hotel_id', 'nights']);

        foreach ($istanbulTourIds as $tour) {
            $hotelPrice = (float) DB::table('hotels')->where('id', $tour->hotel_id)->value('price_per_person');
            $flightCost = 250 + 250; // TAS→IST + IST→TAS
            $finalPrice = round(($hotelPrice * $tour->nights + $flightCost) * (1 + $markup / 100), 2);
            DB::table('tours')->where('id', $tour->id)->update(['price' => $finalPrice, 'updated_at' => $now]);
        }

        // Update Baku tour prices: TAS→GYD ($220) + GYD→TAS ($200) = $420 flight cost
        $bakuTours = DB::table('tours')
            ->where('country_id', $azerbaijanId)
            ->get(['id', 'hotel_id', 'nights']);

        foreach ($bakuTours as $tour) {
            $hotelPrice = (float) DB::table('hotels')->where('id', $tour->hotel_id)->value('price_per_person');
            $flightCost = 220 + 200; // TAS→GYD + GYD→TAS
            $finalPrice = round(($hotelPrice * $tour->nights + $flightCost) * (1 + $markup / 100), 2);
            DB::table('tours')->where('id', $tour->id)->update(['price' => $finalPrice, 'updated_at' => $now]);
        }

        $this->command->info('Fixed flights: ' . count($istGydFlights) . ' IST→GYD changed to TAS→GYD');

        // ═══════════════════════════════════════════════
        // STEP 2: ADD MORE ISTANBUL HOTELS (Fatih & Beyoglu)
        // ═══════════════════════════════════════════════

        $insertHotel = function (string $name, int $resortId, int $catId, float $rating, float $pricePP) use ($now, $usdId) {
            return DB::table('hotels')->insertGetId([
                'name' => $name, 'description' => null, 'address' => null,
                'resort_id' => $resortId, 'hotel_category_id' => $catId,
                'rating' => $rating, 'images' => null, 'amenities' => null, 'is_active' => 1,
                'price_per_person' => $pricePP, 'currency_id' => $usdId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        };

        // Fatih district hotels (5)
        $fatihHotels = [
            $insertHotel('Crowne Plaza Istanbul Old City', $fatihId, $cat5, 4.6, 140.00),
            $insertHotel('Ramada Plaza Istanbul City Center', $fatihId, $cat4, 4.3, 85.00),
            $insertHotel('Legacy Ottoman Hotel', $fatihId, $cat4, 4.4, 90.00),
            $insertHotel('Hotel & Spa & Convention Center', $fatihId, $cat3, 4.1, 50.00),
            $insertHotel('Orient Express Hotel Istanbul', $fatihId, $cat3, 4.0, 45.00),
        ];

        // Beyoglu district hotels (5)
        $beyogluHotels = [
            $insertHotel('Pera Palace Hotel Istanbul', $beyogluId, $cat5, 4.8, 200.00),
            $insertHotel('The Marmara Pera', $beyogluId, $cat5, 4.6, 160.00),
            $insertHotel('CVK Park Bosphorus Hotel', $beyogluId, $cat5, 4.5, 145.00),
            $insertHotel('Witt Istanbul Suites', $beyogluId, $cat4, 4.5, 110.00),
            $insertHotel('Hotel DeCamondo Galata', $beyogluId, $cat4, 4.4, 95.00),
        ];

        $this->command->info('Added 10 Istanbul hotels (5 Fatih + 5 Beyoglu)');

        // ═══════════════════════════════════════════════
        // STEP 3: ADD MORE BAKU RESORTS & HOTELS
        // ═══════════════════════════════════════════════

        $bakuFountainId = DB::table('resorts')->insertGetId([
            'name_en' => 'Fountain Square', 'name_ru' => 'Площадь Фонтанов', 'name_uz' => 'Fontan Maydoni',
            'country_id' => $azerbaijanId, 'city_id' => $bakuCityId, 'is_active' => 1, 'order' => 2,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $bakuBoulevardId = DB::table('resorts')->insertGetId([
            'name_en' => 'Baku Boulevard', 'name_ru' => 'Бакинский бульвар', 'name_uz' => 'Boku Bulvari',
            'country_id' => $azerbaijanId, 'city_id' => $bakuCityId, 'is_active' => 1, 'order' => 3,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // Old City hotels (3 more)
        $bakuOldCityNewHotels = [
            $insertHotel('Shah Palace Hotel Baku', $bakuOldCityId, $cat5, 4.7, 120.00),
            $insertHotel('Sultan Inn Boutique Hotel', $bakuOldCityId, $cat4, 4.4, 70.00),
            $insertHotel('Old City Inn Hotel Baku', $bakuOldCityId, $cat3, 4.1, 45.00),
        ];

        // Fountain Square hotels (3)
        $bakuFountainHotels = [
            $insertHotel('Four Seasons Hotel Baku', $bakuFountainId, $cat5, 4.9, 200.00),
            $insertHotel('Fairmont Baku Flame Towers', $bakuFountainId, $cat5, 4.7, 150.00),
            $insertHotel('Hyatt Regency Baku', $bakuFountainId, $cat5, 4.6, 130.00),
        ];

        // Boulevard hotels (3)
        $bakuBoulevardHotels = [
            $insertHotel('Hilton Baku', $bakuBoulevardId, $cat5, 4.7, 140.00),
            $insertHotel('Intourist Hotel Baku', $bakuBoulevardId, $cat4, 4.3, 75.00),
            $insertHotel('Boulevard Hotel Baku', $bakuBoulevardId, $cat4, 4.2, 65.00),
        ];

        $allNewBakuHotels = array_merge($bakuOldCityNewHotels, $bakuFountainHotels, $bakuBoulevardHotels);
        $bakuHotelResortMap = [];
        foreach ($bakuOldCityNewHotels as $hId) { $bakuHotelResortMap[$hId] = $bakuOldCityId; }
        foreach ($bakuFountainHotels as $hId) { $bakuHotelResortMap[$hId] = $bakuFountainId; }
        foreach ($bakuBoulevardHotels as $hId) { $bakuHotelResortMap[$hId] = $bakuBoulevardId; }

        $this->command->info('Added 9 Baku hotels (3 Old City + 3 Fountain Square + 3 Boulevard)');

        // ═══════════════════════════════════════════════
        // STEP 4: CREATE TOURS FOR NEW HOTELS
        // ═══════════════════════════════════════════════

        $departureDates = [];
        $date = now()->parse('2026-03-01');
        while ($date->lt(now()->parse('2026-07-01'))) {
            $departureDates[] = $date->format('Y-m-d');
            $date->addDays(7);
        }

        $getHotelPrice = function (int $hotelId): float {
            return (float) DB::table('hotels')->where('id', $hotelId)->value('price_per_person');
        };

        $tourCount = 0;

        // Get existing flight lookup by departure_date
        $tasIstByDate = [];
        $istTasByDate = [];
        $tasGydByDate = [];
        $gydTasByDate = [];
        $skdIstByDate = [];
        $istSkdByDate = [];
        $skdGydByDate = [];
        $gydSkdByDate = [];
        $bhkIstByDate = [];
        $istBhkByDate = [];

        $flights = DB::table('flights')
            ->get(['id', 'from_airport_id', 'to_airport_id', 'departure_date']);

        foreach ($flights as $f) {
            $key = $f->departure_date;
            if ($f->from_airport_id == $tasAirportId && $f->to_airport_id == $istAirportId) {
                $tasIstByDate[$key] = $f->id;
            } elseif ($f->from_airport_id == $istAirportId && $f->to_airport_id == $tasAirportId) {
                $istTasByDate[$key] = $f->id;
            } elseif ($f->from_airport_id == $tasAirportId && $f->to_airport_id == $gydAirportId) {
                $tasGydByDate[$key] = $f->id;
            } elseif ($f->from_airport_id == $gydAirportId && $f->to_airport_id == $tasAirportId) {
                $gydTasByDate[$key] = $f->id;
            } elseif ($skdAirportId && $f->from_airport_id == $skdAirportId && $f->to_airport_id == $istAirportId) {
                $skdIstByDate[$key] = $f->id;
            } elseif ($skdAirportId && $f->from_airport_id == $istAirportId && $f->to_airport_id == $skdAirportId) {
                $istSkdByDate[$key] = $f->id;
            } elseif ($bhkAirportId && $f->from_airport_id == $bhkAirportId && $f->to_airport_id == $istAirportId) {
                $bhkIstByDate[$key] = $f->id;
            } elseif ($bhkAirportId && $f->from_airport_id == $istAirportId && $f->to_airport_id == $bhkAirportId) {
                $istBhkByDate[$key] = $f->id;
            }
        }

        foreach ($departureDates as $idx => $depDate) {
            $returnDate10 = now()->parse($depDate)->addDays(10)->format('Y-m-d');

            // Istanbul Fatih & Beyoglu hotels → Combined tours
            $allNewIstanbulHotels = [
                ['ids' => $fatihHotels, 'resort_id' => $fatihId],
                ['ids' => $beyogluHotels, 'resort_id' => $beyogluId],
            ];

            foreach ($allNewIstanbulHotels as $group) {
                foreach ($group['ids'] as $hotelId) {
                    $hotelPrice = $getHotelPrice($hotelId);
                    $flightCost = 250 + 250; // TAS→IST + IST→TAS
                    $finalPrice = round(($hotelPrice * 10 + $flightCost) * (1 + $markup / 100), 2);

                    $tourId = DB::table('tours')->insertGetId([
                        'tour_type_id' => $tourTypeCombined,
                        'program_type_id' => $programStandard,
                        'country_id' => $turkeyId,
                        'resort_id' => $group['resort_id'],
                        'hotel_id' => $hotelId,
                        'transport_type_id' => $airplaneId,
                        'departure_city_id' => $tashkentCityId,
                        'nights' => 10,
                        'price' => $finalPrice,
                        'currency_id' => $usdId,
                        'date_from' => $depDate,
                        'date_to' => $returnDate10,
                        'adults' => 2,
                        'children' => 0,
                        'meal_type_id' => $mealBB,
                        'is_available' => 1,
                        'is_hot' => $idx < 3 ? 1 : 0,
                        'instant_confirmation' => 0,
                        'no_stop_sale' => 1,
                        'child_bed_separate' => 0,
                        'comfortable_seats' => 0,
                        'markup_percent' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if (isset($tasIstByDate[$depDate])) {
                        DB::table('tour_flight')->insert([
                            'tour_id' => $tourId, 'flight_id' => $tasIstByDate[$depDate], 'direction' => 'outbound',
                        ]);
                    }
                    if (isset($istTasByDate[$returnDate10])) {
                        DB::table('tour_flight')->insert([
                            'tour_id' => $tourId, 'flight_id' => $istTasByDate[$returnDate10], 'direction' => 'return',
                        ]);
                    }
                    $tourCount++;
                }
            }

            // Samarkand → Istanbul Fatih & Beyoglu hotels
            if ($samarkandCityId) {
                foreach ($allNewIstanbulHotels as $group) {
                    foreach ($group['ids'] as $hotelId) {
                        $hotelPrice = $getHotelPrice($hotelId);
                        $flightCost = 270 + 270;
                        $finalPrice = round(($hotelPrice * 10 + $flightCost) * (1 + $markup / 100), 2);

                        $tourId = DB::table('tours')->insertGetId([
                            'tour_type_id' => $tourTypeCombined,
                            'program_type_id' => $programStandard,
                            'country_id' => $turkeyId,
                            'resort_id' => $group['resort_id'],
                            'hotel_id' => $hotelId,
                            'transport_type_id' => $airplaneId,
                            'departure_city_id' => $samarkandCityId,
                            'nights' => 10,
                            'price' => $finalPrice,
                            'currency_id' => $usdId,
                            'date_from' => $depDate,
                            'date_to' => $returnDate10,
                            'adults' => 2, 'children' => 0,
                            'meal_type_id' => $mealBB,
                            'is_available' => 1, 'is_hot' => $idx < 3 ? 1 : 0,
                            'instant_confirmation' => 0, 'no_stop_sale' => 1,
                            'child_bed_separate' => 0, 'comfortable_seats' => 0,
                            'markup_percent' => null,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);

                        if (isset($skdIstByDate[$depDate])) {
                            DB::table('tour_flight')->insert([
                                'tour_id' => $tourId, 'flight_id' => $skdIstByDate[$depDate], 'direction' => 'outbound',
                            ]);
                        }
                        if (isset($istSkdByDate[$returnDate10])) {
                            DB::table('tour_flight')->insert([
                                'tour_id' => $tourId, 'flight_id' => $istSkdByDate[$returnDate10], 'direction' => 'return',
                            ]);
                        }
                        $tourCount++;
                    }
                }
            }

            // Bukhara → Istanbul Fatih & Beyoglu hotels
            if ($bukharaCityId) {
                foreach ($allNewIstanbulHotels as $group) {
                    foreach ($group['ids'] as $hotelId) {
                        $hotelPrice = $getHotelPrice($hotelId);
                        $flightCost = 280 + 280;
                        $finalPrice = round(($hotelPrice * 10 + $flightCost) * (1 + $markup / 100), 2);

                        $tourId = DB::table('tours')->insertGetId([
                            'tour_type_id' => $tourTypeCombined,
                            'program_type_id' => $programStandard,
                            'country_id' => $turkeyId,
                            'resort_id' => $group['resort_id'],
                            'hotel_id' => $hotelId,
                            'transport_type_id' => $airplaneId,
                            'departure_city_id' => $bukharaCityId,
                            'nights' => 10,
                            'price' => $finalPrice,
                            'currency_id' => $usdId,
                            'date_from' => $depDate,
                            'date_to' => $returnDate10,
                            'adults' => 2, 'children' => 0,
                            'meal_type_id' => $mealBB,
                            'is_available' => 1, 'is_hot' => $idx < 3 ? 1 : 0,
                            'instant_confirmation' => 0, 'no_stop_sale' => 1,
                            'child_bed_separate' => 0, 'comfortable_seats' => 0,
                            'markup_percent' => null,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);

                        if (isset($bhkIstByDate[$depDate])) {
                            DB::table('tour_flight')->insert([
                                'tour_id' => $tourId, 'flight_id' => $bhkIstByDate[$depDate], 'direction' => 'outbound',
                            ]);
                        }
                        if (isset($istBhkByDate[$returnDate10])) {
                            DB::table('tour_flight')->insert([
                                'tour_id' => $tourId, 'flight_id' => $istBhkByDate[$returnDate10], 'direction' => 'return',
                            ]);
                        }
                        $tourCount++;
                    }
                }
            }

            // Baku new hotels → Combined tours
            foreach ($allNewBakuHotels as $hotelId) {
                $hotelPrice = $getHotelPrice($hotelId);
                $flightCost = 220 + 200; // TAS→GYD + GYD→TAS
                $finalPrice = round(($hotelPrice * 10 + $flightCost) * (1 + $markup / 100), 2);

                $tourId = DB::table('tours')->insertGetId([
                    'tour_type_id' => $tourTypeCombined,
                    'program_type_id' => $programStandard,
                    'country_id' => $azerbaijanId,
                    'resort_id' => $bakuHotelResortMap[$hotelId],
                    'hotel_id' => $hotelId,
                    'transport_type_id' => $airplaneId,
                    'departure_city_id' => $tashkentCityId,
                    'nights' => 10,
                    'price' => $finalPrice,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate10,
                    'adults' => 2,
                    'children' => 0,
                    'meal_type_id' => $mealBB,
                    'is_available' => 1,
                    'is_hot' => $idx < 3 ? 1 : 0,
                    'instant_confirmation' => 0,
                    'no_stop_sale' => 1,
                    'child_bed_separate' => 0,
                    'comfortable_seats' => 0,
                    'markup_percent' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if (isset($tasGydByDate[$depDate])) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $tasGydByDate[$depDate], 'direction' => 'outbound',
                    ]);
                }
                if (isset($gydTasByDate[$returnDate10])) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $gydTasByDate[$returnDate10], 'direction' => 'return',
                    ]);
                }
                $tourCount++;
            }
        }

        $this->command->info("Created {$tourCount} new tours for added hotels.");

        // ═══════════════════════════════════════════════
        // SUMMARY
        // ═══════════════════════════════════════════════
        $totalHotels = DB::table('hotels')->count();
        $totalTours = DB::table('tours')->count();
        $totalFlights = DB::table('flights')->count();

        // Verify: all flights involve a departure city airport (TAS, SKD, or BHK)
        $departureCityAirportIds = array_filter([$tasAirportId, $skdAirportId, $bhkAirportId]);
        $nonDepartureFlights = DB::table('flights')
            ->whereNotIn('from_airport_id', $departureCityAirportIds)
            ->whereNotIn('to_airport_id', $departureCityAirportIds)
            ->count();

        $this->command->info("Total hotels: {$totalHotels}");
        $this->command->info("Total tours: {$totalTours}");
        $this->command->info("Total flights: {$totalFlights}");
        $this->command->info("Flights NOT involving departure cities: {$nonDepartureFlights} (should be 0)");
    }
}
