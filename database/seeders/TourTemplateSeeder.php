<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates 2 tour templates:
 * 1. Istanbul + Nice: TAS→IST→NCE→IST→TAS (4 legs, 2n IST + 4n NCE)
 * 2. Istanbul + Baku: TAS→IST→GYD→TAS (3 legs, 2n IST + 4n Baku)
 *
 * Legs use departure dates from the first available flight date (2026-04-13).
 * TAS↔IST and IST→TAS = local_db, IST↔NCE and IST↔GYD = rapidapi.
 */
class TourTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Clean old orphaned flight paths
        DB::table('flight_path_stays')->whereIn('flight_path_id',
            DB::table('flight_paths')->whereNull('tour_template_id')->pluck('id')
        )->delete();
        DB::table('flight_path_legs')->whereIn('flight_path_id',
            DB::table('flight_paths')->whereNull('tour_template_id')->pluck('id')
        )->delete();
        DB::table('flight_paths')->whereNull('tour_template_id')->delete();

        // Clean old templates if re-running
        DB::table('tour_template_stays')->delete();
        DB::table('tour_template_legs')->delete();
        DB::table('tour_templates')->delete();

        $tashkentId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $istanbulId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $niceId = DB::table('cities')->where('name_en', 'Nice')->value('id');
        $bakuId = DB::table('cities')->where('name_en', 'Baku')->value('id');

        if (! $tashkentId || ! $istanbulId) {
            $this->command->warn('TourTemplateSeeder: cities not found. Run BasicDataSeeder first.');
            return;
        }

        // ═══════════════════════════════════════════
        // Template 1: Istanbul + Nice
        // Route: TAS → IST (2n) → NCE (4n) → IST → TAS
        // ═══════════════════════════════════════════
        $t1Id = DB::table('tour_templates')->insertGetId([
            'route_name' => 'Istanbul + Nice',
            'departure_city_id' => $tashkentId,
            'total_nights' => 6,
            'is_active' => true,
            'status' => 'active',
            'base_currency' => 'USD',
            'margin_percent' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Stays
        DB::table('tour_template_stays')->insert([
            ['tour_template_id' => $t1Id, 'city_id' => $istanbulId, 'stay_order' => 1, 'nights' => 2,
             'check_in_date' => '2026-04-13', 'check_out_date' => '2026-04-15',
             'created_at' => now(), 'updated_at' => now()],
            ['tour_template_id' => $t1Id, 'city_id' => $niceId, 'stay_order' => 2, 'nights' => 4,
             'check_in_date' => '2026-04-15', 'check_out_date' => '2026-04-19',
             'created_at' => now(), 'updated_at' => now()],
        ]);

        // Legs: 4 legs
        // Leg 1: TAS→IST Apr 13 (local_db)
        $leg1 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t1Id, 'leg_order' => 1,
            'departure_city_id' => $tashkentId, 'arrival_city_id' => $istanbulId,
            'departure_date' => '2026-04-13', 'arrival_date' => '2026-04-13',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 2: IST→NCE Apr 15 (rapidapi)
        $leg2 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t1Id, 'leg_order' => 2,
            'departure_city_id' => $istanbulId, 'arrival_city_id' => $niceId,
            'departure_date' => '2026-04-15', 'arrival_date' => '2026-04-15',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'rapidapi', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 3: NCE→IST Apr 19 (rapidapi, paired with leg 2)
        $leg3 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t1Id, 'leg_order' => 3,
            'departure_city_id' => $niceId, 'arrival_city_id' => $istanbulId,
            'departure_date' => '2026-04-19', 'arrival_date' => '2026-04-19',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'rapidapi', 'round_trip_pair_id' => $leg2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Link leg 2 to leg 3 (bidirectional pair)
        DB::table('tour_template_legs')->where('id', $leg2)->update(['round_trip_pair_id' => $leg3]);

        // Leg 4: IST→TAS Apr 19 (local_db)
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t1Id, 'leg_order' => 4,
            'departure_city_id' => $istanbulId, 'arrival_city_id' => $tashkentId,
            'departure_date' => '2026-04-19', 'arrival_date' => '2026-04-19',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->command->info("Created template: Istanbul + Nice (ID: {$t1Id})");

        // ═══════════════════════════════════════════
        // Template 2: Istanbul + Baku
        // Route: TAS → IST (2n) → GYD (4n) → TAS
        // ═══════════════════════════════════════════
        if (! $bakuId) {
            $this->command->warn('Baku city not found, skipping Istanbul + Baku template.');
            return;
        }

        $t2Id = DB::table('tour_templates')->insertGetId([
            'route_name' => 'Istanbul + Baku',
            'departure_city_id' => $tashkentId,
            'total_nights' => 6,
            'is_active' => true,
            'status' => 'active',
            'base_currency' => 'USD',
            'margin_percent' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Stays
        DB::table('tour_template_stays')->insert([
            ['tour_template_id' => $t2Id, 'city_id' => $istanbulId, 'stay_order' => 1, 'nights' => 2,
             'check_in_date' => '2026-04-13', 'check_out_date' => '2026-04-15',
             'created_at' => now(), 'updated_at' => now()],
            ['tour_template_id' => $t2Id, 'city_id' => $bakuId, 'stay_order' => 2, 'nights' => 4,
             'check_in_date' => '2026-04-15', 'check_out_date' => '2026-04-19',
             'created_at' => now(), 'updated_at' => now()],
        ]);

        // Leg 1: TAS→IST Apr 13 (local_db)
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t2Id, 'leg_order' => 1,
            'departure_city_id' => $tashkentId, 'arrival_city_id' => $istanbulId,
            'departure_date' => '2026-04-13', 'arrival_date' => '2026-04-13',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 2: IST→GYD Apr 15 (rapidapi)
        $bLeg2 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t2Id, 'leg_order' => 2,
            'departure_city_id' => $istanbulId, 'arrival_city_id' => $bakuId,
            'departure_date' => '2026-04-15', 'arrival_date' => '2026-04-15',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'rapidapi', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 3: GYD→TAS Apr 19 (local_db)
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t2Id, 'leg_order' => 3,
            'departure_city_id' => $bakuId, 'arrival_city_id' => $tashkentId,
            'departure_date' => '2026-04-19', 'arrival_date' => '2026-04-19',
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->command->info("Created template: Istanbul + Baku (ID: {$t2Id})");
    }
}
