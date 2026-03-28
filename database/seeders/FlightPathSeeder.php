<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates flight paths from existing flights.
 * Requires: FlightSeeder to run first.
 *
 * Istanbul+Nice: TAS→IST, IST→NCE, NCE→IST (3 legs, 7 nights: 2 IST + 4 NCE + 1 transit)
 * Istanbul+Baku: TAS→IST, IST→GYD, GYD→IST, GYD→TAS (4 legs, 7 nights: 2 IST + 4 Baku + 1 transit)
 */
class FlightPathSeeder extends Seeder
{
    public function run(): void
    {
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        $tashkentId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $istanbulId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $niceId = DB::table('cities')->where('name_en', 'Nice')->value('id');
        $bakuId = DB::table('cities')->where('name_en', 'Baku')->value('id');

        $tasId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istId = DB::table('airports')->where('code', 'IST')->value('id');
        $nceId = DB::table('airports')->where('code', 'NCE')->value('id');
        $gydId = DB::table('airports')->where('code', 'GYD')->value('id');

        if (! $tashkentId || ! $istanbulId || ! $usdId) {
            $this->command->error('Missing reference data. Run db:seed + FlightSeeder + HotelSeeder first.');
            return;
        }

        // Get all TAS→IST flights (these define departure dates)
        $tasIstFlights = DB::table('flights')
            ->where('from_airport_id', $tasId)->where('to_airport_id', $istId)
            ->where('is_active', true)->orderBy('departure_date')->get();

        if ($tasIstFlights->isEmpty()) {
            $this->command->error('No TAS→IST flights. Run FlightSeeder first.');
            return;
        }

        // Index all flights by route-date for quick lookup
        $flightIndex = [];
        $allFlights = DB::table('flights')->where('is_active', true)->get();
        foreach ($allFlights as $f) {
            $from = DB::table('airports')->where('id', $f->from_airport_id)->value('code');
            $to = DB::table('airports')->where('id', $f->to_airport_id)->value('code');
            $flightIndex["{$from}-{$to}-{$f->departure_date}"] = $f;
        }

        $niceCount = 0;
        $bakuCount = 0;

        foreach ($tasIstFlights as $tasIst) {
            $depDate = $tasIst->departure_date;
            $istNceDate = date('Y-m-d', strtotime($depDate . ' +2 days'));
            $nceIstDate = date('Y-m-d', strtotime($depDate . ' +6 days'));
            $istGydDate = date('Y-m-d', strtotime($depDate . ' +2 days'));
            $gydIstDate = date('Y-m-d', strtotime($depDate . ' +6 days'));
            $returnDate = date('Y-m-d', strtotime($depDate . ' +7 days'));

            // ── Istanbul + Nice ──
            if ($nceId) {
                $istNce = $flightIndex["IST-NCE-{$istNceDate}"] ?? null;
                $nceIst = $flightIndex["NCE-IST-{$nceIstDate}"] ?? null;

                $exists = DB::table('flight_paths')
                    ->where('route_name', 'Istanbul + Nice')
                    ->where('departure_date', $depDate)
                    ->exists();

                if (! $exists) {
                    $totalPrice = (float) $tasIst->price_adult
                        + ($istNce ? (float) $istNce->price_adult : 0)
                        + ($nceIst ? (float) $nceIst->price_adult : 0);

                    $fpId = DB::table('flight_paths')->insertGetId([
                        'route_name' => 'Istanbul + Nice',
                        'departure_date' => $depDate,
                        'departure_city_id' => $tashkentId,
                        'total_price' => $totalPrice,
                        'currency_id' => $usdId,
                        'nights' => 7,
                        'is_available' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);

                    // Legs
                    $legOrder = 1;
                    DB::table('flight_path_legs')->insert([
                        'flight_path_id' => $fpId, 'flight_id' => $tasIst->id,
                        'leg_order' => $legOrder++, 'direction' => 'outbound',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($istNce) {
                        DB::table('flight_path_legs')->insert([
                            'flight_path_id' => $fpId, 'flight_id' => $istNce->id,
                            'leg_order' => $legOrder++, 'direction' => 'outbound',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    if ($nceIst) {
                        DB::table('flight_path_legs')->insert([
                            'flight_path_id' => $fpId, 'flight_id' => $nceIst->id,
                            'leg_order' => $legOrder++, 'direction' => 'return',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }

                    // Stays
                    DB::table('flight_path_stays')->insert([
                        ['flight_path_id' => $fpId, 'city_id' => $istanbulId, 'stay_order' => 1, 'nights' => 2, 'created_at' => now(), 'updated_at' => now()],
                        ['flight_path_id' => $fpId, 'city_id' => $niceId, 'stay_order' => 2, 'nights' => 4, 'created_at' => now(), 'updated_at' => now()],
                    ]);

                    $niceCount++;
                }
            }

            // ── Istanbul + Baku ──
            if ($bakuId && $gydId) {
                $istGyd = $flightIndex["IST-GYD-{$istGydDate}"] ?? null;
                $gydIst = $flightIndex["GYD-IST-{$gydIstDate}"] ?? null;
                $gydTas = $flightIndex["GYD-TAS-{$returnDate}"] ?? null;

                $exists = DB::table('flight_paths')
                    ->where('route_name', 'Istanbul + Baku')
                    ->where('departure_date', $depDate)
                    ->exists();

                if (! $exists) {
                    $totalPrice = (float) $tasIst->price_adult
                        + ($istGyd ? (float) $istGyd->price_adult : 0)
                        + ($gydIst ? (float) $gydIst->price_adult : 0)
                        + ($gydTas ? (float) $gydTas->price_adult : 0);

                    $fpId = DB::table('flight_paths')->insertGetId([
                        'route_name' => 'Istanbul + Baku',
                        'departure_date' => $depDate,
                        'departure_city_id' => $tashkentId,
                        'total_price' => $totalPrice,
                        'currency_id' => $usdId,
                        'nights' => 7,
                        'is_available' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);

                    // Legs
                    $legOrder = 1;
                    DB::table('flight_path_legs')->insert([
                        'flight_path_id' => $fpId, 'flight_id' => $tasIst->id,
                        'leg_order' => $legOrder++, 'direction' => 'outbound',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($istGyd) {
                        DB::table('flight_path_legs')->insert([
                            'flight_path_id' => $fpId, 'flight_id' => $istGyd->id,
                            'leg_order' => $legOrder++, 'direction' => 'outbound',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    if ($gydIst) {
                        DB::table('flight_path_legs')->insert([
                            'flight_path_id' => $fpId, 'flight_id' => $gydIst->id,
                            'leg_order' => $legOrder++, 'direction' => 'return',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    if ($gydTas) {
                        DB::table('flight_path_legs')->insert([
                            'flight_path_id' => $fpId, 'flight_id' => $gydTas->id,
                            'leg_order' => $legOrder++, 'direction' => 'return',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }

                    // Stays
                    DB::table('flight_path_stays')->insert([
                        ['flight_path_id' => $fpId, 'city_id' => $istanbulId, 'stay_order' => 1, 'nights' => 2, 'created_at' => now(), 'updated_at' => now()],
                        ['flight_path_id' => $fpId, 'city_id' => $bakuId, 'stay_order' => 2, 'nights' => 4, 'created_at' => now(), 'updated_at' => now()],
                    ]);

                    $bakuCount++;
                }
            }
        }

        $this->command->info("Created {$niceCount} Istanbul+Nice + {$bakuCount} Istanbul+Baku flight paths.");
    }
}
