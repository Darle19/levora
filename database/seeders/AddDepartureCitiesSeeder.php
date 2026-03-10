<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddDepartureCitiesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ─── Check if departure cities already exist ───
        $samarkandCityId = DB::table('cities')->where('name_en', 'Samarkand')->value('id');
        $bukharaCityId = DB::table('cities')->where('name_en', 'Bukhara')->value('id');

        $uzbekistanId = DB::table('countries')->where('code', 'UZB')->value('id');

        // ─── Create Samarkand if missing ───
        if (! $samarkandCityId) {
            $samarkandCityId = DB::table('cities')->insertGetId([
                'name_en' => 'Samarkand', 'name_ru' => 'Самарканд', 'name_uz' => 'Samarqand',
                'country_id' => $uzbekistanId, 'is_active' => 1, 'order' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->command->info('Created Samarkand city');
        }

        // ─── Create Bukhara if missing ───
        if (! $bukharaCityId) {
            $bukharaCityId = DB::table('cities')->insertGetId([
                'name_en' => 'Bukhara', 'name_ru' => 'Бухара', 'name_uz' => 'Buxoro',
                'country_id' => $uzbekistanId, 'is_active' => 1, 'order' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->command->info('Created Bukhara city');
        }

        // ─── Create airports if missing ───
        $skdAirportId = DB::table('airports')->where('code', 'SKD')->value('id');
        if (! $skdAirportId) {
            $skdAirportId = DB::table('airports')->insertGetId([
                'name_en' => 'Samarkand International Airport', 'name_ru' => 'Международный аэропорт Самарканда',
                'name_uz' => 'Samarqand xalqaro aeroporti',
                'code' => 'SKD', 'city_id' => $samarkandCityId, 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->command->info('Created Samarkand airport (SKD)');
        }

        $bhkAirportId = DB::table('airports')->where('code', 'BHK')->value('id');
        if (! $bhkAirportId) {
            $bhkAirportId = DB::table('airports')->insertGetId([
                'name_en' => 'Bukhara International Airport', 'name_ru' => 'Международный аэропорт Бухары',
                'name_uz' => 'Buxoro xalqaro aeroporti',
                'code' => 'BHK', 'city_id' => $bukharaCityId, 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->command->info('Created Bukhara airport (BHK)');
        }

        // ─── Existing IDs ───
        $tasAirportId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istAirportId = DB::table('airports')->where('code', 'IST')->value('id');
        $dpsAirportId = DB::table('airports')->where('code', 'DPS')->value('id');
        $busAirportId = DB::table('airports')->where('code', 'BUS')->value('id');
        $cdgAirportId = DB::table('airports')->where('code', 'CDG')->value('id');
        $gydAirportId = DB::table('airports')->where('code', 'GYD')->value('id');

        $centrumAirId = DB::table('airlines')->where('code', 'C2')->value('id');
        $batikAirId = DB::table('airlines')->where('code', 'ID')->value('id');
        $turkishId = DB::table('airlines')->where('code', 'TK')->value('id');
        $uzAirsId = DB::table('airlines')->where('code', 'HY')->value('id');

        $usdId = 1;
        $markup = (float) (Setting::getValue('tour_markup_percent', 15) ?? 15);

        // ─── Create flights from Samarkand and Bukhara ───
        $departureDates = [];
        $date = now()->parse('2026-03-01');
        while ($date->lt(now()->parse('2026-07-01'))) {
            $departureDates[] = $date->format('Y-m-d');
            $date->addDays(7);
        }

        $flightCounter = 500;

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

        // Check if we already have Samarkand flights
        $existingSkdFlights = DB::table('flights')
            ->where('from_airport_id', $skdAirportId)
            ->orWhere('to_airport_id', $skdAirportId)
            ->count();

        if ($existingSkdFlights > 0) {
            $this->command->info('Samarkand flights already exist, skipping flight/tour creation.');
            return;
        }

        $skdFlights = [];
        $bhkFlights = [];
        $flightCount = 0;
        $tourCount = 0;

        foreach ($departureDates as $depDate) {
            $returnDate7 = now()->parse($depDate)->addDays(7)->format('Y-m-d');
            $returnDate10 = now()->parse($depDate)->addDays(10)->format('Y-m-d');

            // Samarkand → Istanbul
            if ($istAirportId && $centrumAirId) {
                $fNum = 'C2' . $flightCounter++;
                $outId = $insertFlight($centrumAirId, $skdAirportId, $istAirportId, $fNum, $depDate, '09:30', '13:00', $depDate, 270.00, 195.00, 150);
                $fNum = 'C2' . $flightCounter++;
                $retId = $insertFlight($centrumAirId, $istAirportId, $skdAirportId, $fNum, $returnDate10, '15:00', '20:30', $returnDate10, 270.00, 195.00, 150);
                $skdFlights[$depDate]['istanbul'] = ['outbound' => $outId, 'return' => $retId];
                $flightCount += 2;
            }

            // Samarkand → Bali
            if ($dpsAirportId && $batikAirId) {
                $fNum = 'ID' . $flightCounter++;
                $outId = $insertFlight($batikAirId, $skdAirportId, $dpsAirportId, $fNum, $depDate, '23:00', '13:00', now()->parse($depDate)->addDay()->format('Y-m-d'), 480.00, 360.00, 180);
                $fNum = 'ID' . $flightCounter++;
                $retId = $insertFlight($batikAirId, $dpsAirportId, $skdAirportId, $fNum, $returnDate10, '15:00', '23:00', $returnDate10, 480.00, 360.00, 180);
                $skdFlights[$depDate]['bali'] = ['outbound' => $outId, 'return' => $retId];
                $flightCount += 2;
            }

            // Samarkand → Batumi
            if ($busAirportId && $centrumAirId) {
                $fNum = 'C2' . $flightCounter++;
                $outId = $insertFlight($centrumAirId, $skdAirportId, $busAirportId, $fNum, $depDate, '10:00', '12:30', $depDate, 220.00, 165.00, 120);
                $fNum = 'C2' . $flightCounter++;
                $retId = $insertFlight($centrumAirId, $busAirportId, $skdAirportId, $fNum, $returnDate7, '14:00', '16:30', $returnDate7, 220.00, 165.00, 120);
                $skdFlights[$depDate]['batumi'] = ['outbound' => $outId, 'return' => $retId];
                $flightCount += 2;
            }

            // Samarkand → Paris
            if ($cdgAirportId && $turkishId && $uzAirsId) {
                $fNum = 'TK' . $flightCounter++;
                $outId = $insertFlight($turkishId, $skdAirportId, $cdgAirportId, $fNum, $depDate, '07:00', '15:30', $depDate, 420.00, 315.00, 180);
                $fNum = 'HY' . $flightCounter++;
                $retId = $insertFlight($uzAirsId, $cdgAirportId, $skdAirportId, $fNum, $returnDate7, '17:00', '03:00', now()->parse($returnDate7)->addDay()->format('Y-m-d'), 400.00, 300.00, 180);
                $skdFlights[$depDate]['paris'] = ['outbound' => $outId, 'return' => $retId];
                $flightCount += 2;
            }

            // Bukhara → Istanbul
            if ($istAirportId && $centrumAirId) {
                $fNum = 'C2' . $flightCounter++;
                $outId = $insertFlight($centrumAirId, $bhkAirportId, $istAirportId, $fNum, $depDate, '10:30', '14:00', $depDate, 280.00, 200.00, 120);
                $fNum = 'C2' . $flightCounter++;
                $retId = $insertFlight($centrumAirId, $istAirportId, $bhkAirportId, $fNum, $returnDate10, '16:00', '21:30', $returnDate10, 280.00, 200.00, 120);
                $bhkFlights[$depDate]['istanbul'] = ['outbound' => $outId, 'return' => $retId];
                $flightCount += 2;
            }

            // Bukhara → Batumi
            if ($busAirportId && $centrumAirId) {
                $fNum = 'C2' . $flightCounter++;
                $outId = $insertFlight($centrumAirId, $bhkAirportId, $busAirportId, $fNum, $depDate, '11:00', '13:30', $depDate, 230.00, 170.00, 100);
                $fNum = 'C2' . $flightCounter++;
                $retId = $insertFlight($centrumAirId, $busAirportId, $bhkAirportId, $fNum, $returnDate7, '15:00', '17:30', $returnDate7, 230.00, 170.00, 100);
                $bhkFlights[$depDate]['batumi'] = ['outbound' => $outId, 'return' => $retId];
                $flightCount += 2;
            }
        }

        $this->command->info("Created {$flightCount} flights for Samarkand and Bukhara departures.");

        // ─── Create tours from Samarkand and Bukhara ───
        // Get a sample of existing Tashkent tours to replicate with new departure cities
        $tashkentCityId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');

        $existingTours = DB::table('tours')
            ->where('departure_city_id', $tashkentCityId)
            ->get();

        // Group tours by country for targeted replication
        $toursByCountry = $existingTours->groupBy('country_id');

        $turkeyId = DB::table('countries')->where('code', 'TUR')->value('id');
        $indonesiaId = DB::table('countries')->where('code', 'IDN')->value('id');
        $georgiaId = DB::table('countries')->where('code', 'GEO')->value('id');
        $franceId = DB::table('countries')->where('code', 'FRA')->value('id');

        foreach ($departureDates as $idx => $depDate) {
            $returnDate7 = now()->parse($depDate)->addDays(7)->format('Y-m-d');
            $returnDate10 = now()->parse($depDate)->addDays(10)->format('Y-m-d');

            // Get Tashkent tours for this date to replicate
            $dateTours = $existingTours->where('date_from', $depDate);

            foreach ($dateTours as $srcTour) {
                $hotelPrice = (float) DB::table('hotels')->where('id', $srcTour->hotel_id)->value('price_per_person');

                // ── Samarkand versions ──
                $skdFlightInfo = null;
                $skdFlightCost = 0;

                if ($srcTour->country_id == $turkeyId && isset($skdFlights[$depDate]['istanbul'])) {
                    $skdFlightInfo = $skdFlights[$depDate]['istanbul'];
                    $skdFlightCost = 270 + 270;
                } elseif ($srcTour->country_id == $indonesiaId && isset($skdFlights[$depDate]['bali'])) {
                    $skdFlightInfo = $skdFlights[$depDate]['bali'];
                    $skdFlightCost = 480 + 480;
                } elseif ($srcTour->country_id == $georgiaId && isset($skdFlights[$depDate]['batumi'])) {
                    $skdFlightInfo = $skdFlights[$depDate]['batumi'];
                    $skdFlightCost = 220 + 220;
                } elseif ($srcTour->country_id == $franceId && isset($skdFlights[$depDate]['paris'])) {
                    $skdFlightInfo = $skdFlights[$depDate]['paris'];
                    $skdFlightCost = 420 + 400;
                }

                if ($skdFlightInfo) {
                    $finalPrice = round(($hotelPrice * $srcTour->nights + $skdFlightCost) * (1 + $markup / 100), 2);

                    $tourId = DB::table('tours')->insertGetId([
                        'tour_type_id' => $srcTour->tour_type_id,
                        'program_type_id' => $srcTour->program_type_id,
                        'country_id' => $srcTour->country_id,
                        'resort_id' => $srcTour->resort_id,
                        'hotel_id' => $srcTour->hotel_id,
                        'transport_type_id' => $srcTour->transport_type_id,
                        'departure_city_id' => $samarkandCityId,
                        'nights' => $srcTour->nights,
                        'price' => $finalPrice,
                        'currency_id' => $srcTour->currency_id,
                        'date_from' => $srcTour->date_from,
                        'date_to' => $srcTour->date_to,
                        'adults' => $srcTour->adults,
                        'children' => $srcTour->children,
                        'meal_type_id' => $srcTour->meal_type_id,
                        'is_available' => 1,
                        'is_hot' => $srcTour->is_hot,
                        'instant_confirmation' => $srcTour->instant_confirmation,
                        'no_stop_sale' => $srcTour->no_stop_sale,
                        'child_bed_separate' => $srcTour->child_bed_separate,
                        'comfortable_seats' => $srcTour->comfortable_seats,
                        'markup_percent' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('tour_flight')->insert([
                        ['tour_id' => $tourId, 'flight_id' => $skdFlightInfo['outbound'], 'direction' => 'outbound'],
                        ['tour_id' => $tourId, 'flight_id' => $skdFlightInfo['return'], 'direction' => 'return'],
                    ]);
                    $tourCount++;
                }

                // ── Bukhara versions (Istanbul & Batumi only) ──
                $bhkFlightInfo = null;
                $bhkFlightCost = 0;

                if ($srcTour->country_id == $turkeyId && isset($bhkFlights[$depDate]['istanbul'])) {
                    $bhkFlightInfo = $bhkFlights[$depDate]['istanbul'];
                    $bhkFlightCost = 280 + 280;
                } elseif ($srcTour->country_id == $georgiaId && isset($bhkFlights[$depDate]['batumi'])) {
                    $bhkFlightInfo = $bhkFlights[$depDate]['batumi'];
                    $bhkFlightCost = 230 + 230;
                }

                if ($bhkFlightInfo) {
                    $finalPrice = round(($hotelPrice * $srcTour->nights + $bhkFlightCost) * (1 + $markup / 100), 2);

                    $tourId = DB::table('tours')->insertGetId([
                        'tour_type_id' => $srcTour->tour_type_id,
                        'program_type_id' => $srcTour->program_type_id,
                        'country_id' => $srcTour->country_id,
                        'resort_id' => $srcTour->resort_id,
                        'hotel_id' => $srcTour->hotel_id,
                        'transport_type_id' => $srcTour->transport_type_id,
                        'departure_city_id' => $bukharaCityId,
                        'nights' => $srcTour->nights,
                        'price' => $finalPrice,
                        'currency_id' => $srcTour->currency_id,
                        'date_from' => $srcTour->date_from,
                        'date_to' => $srcTour->date_to,
                        'adults' => $srcTour->adults,
                        'children' => $srcTour->children,
                        'meal_type_id' => $srcTour->meal_type_id,
                        'is_available' => 1,
                        'is_hot' => $srcTour->is_hot,
                        'instant_confirmation' => $srcTour->instant_confirmation,
                        'no_stop_sale' => $srcTour->no_stop_sale,
                        'child_bed_separate' => $srcTour->child_bed_separate,
                        'comfortable_seats' => $srcTour->comfortable_seats,
                        'markup_percent' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('tour_flight')->insert([
                        ['tour_id' => $tourId, 'flight_id' => $bhkFlightInfo['outbound'], 'direction' => 'outbound'],
                        ['tour_id' => $tourId, 'flight_id' => $bhkFlightInfo['return'], 'direction' => 'return'],
                    ]);
                    $tourCount++;
                }
            }
        }

        $this->command->info("Created {$tourCount} tours from Samarkand and Bukhara.");

        $totalTours = DB::table('tours')->count();
        $departureCities = DB::table('tours')
            ->join('cities', 'tours.departure_city_id', '=', 'cities.id')
            ->selectRaw('cities.name_en, count(*) as tour_count')
            ->groupBy('cities.name_en')
            ->get();

        $this->command->info("Total tours: {$totalTours}");
        foreach ($departureCities as $dc) {
            $this->command->info("  {$dc->name_en}: {$dc->tour_count} tours");
        }
    }
}
