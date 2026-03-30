<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates 2 tour templates with day_offset-based legs:
 *
 * 1. Istanbul + Nice: TAS→IST(+0) → IST→NCE(+2) → NCE→IST(+6) → IST→TAS(+6)
 *    Stays: Istanbul 2n, Nice 4n
 *
 * 2. Istanbul + Baku: TAS→IST(+0) → IST→GYD(+2) → GYD→TAS(+6)
 *    Stays: Istanbul 2n, Baku 4n
 *
 * Use "Generate Flights" with base dates (e.g. Apr 13, Apr 20) to create paths.
 */
class TourTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Clean old orphaned flight paths (no template)
        $orphanIds = DB::table('flight_paths')->whereNull('tour_template_id')->pluck('id');
        if ($orphanIds->isNotEmpty()) {
            DB::table('flight_path_stays')->whereIn('flight_path_id', $orphanIds)->delete();
            DB::table('flight_path_legs')->whereIn('flight_path_id', $orphanIds)->delete();
            DB::table('flight_paths')->whereIn('id', $orphanIds)->delete();
            $this->command->info("Deleted {$orphanIds->count()} orphaned flight paths.");
        }

        // Clean old templates if re-running
        DB::table('tour_template_legs')->delete();
        DB::table('tour_template_stays')->delete();
        DB::table('tour_templates')->delete();

        $tashkentId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $istanbulId = DB::table('cities')->where('name_en', 'Istanbul')->value('id');
        $niceId = DB::table('cities')->where('name_en', 'Nice')->value('id');
        $bakuId = DB::table('cities')->where('name_en', 'Baku')->value('id');

        if (! $tashkentId || ! $istanbulId) {
            $this->command->warn('TourTemplateSeeder: cities not found. Run BasicDataSeeder first.');
            return;
        }

        // Airlines
        $c2Id = DB::table('airlines')->where('code', 'C2')->value('id');  // Centrum Air
        $tkId = DB::table('airlines')->where('code', 'TK')->value('id');  // Turkish Airlines
        $j2Id = DB::table('airlines')->where('code', 'J2')->value('id');  // Azerbaijan Airlines

        // ═══════════════════════════════════════════
        // Template 1: Istanbul + Nice
        // TAS→IST(+0) → IST→NCE(+2) → NCE→IST(+6) → IST→TAS(+6)
        // ═══════════════════════════════════════════
        $t1Id = DB::table('tour_templates')->insertGetId([
            'route_name' => 'Istanbul + Nice',
            'departure_city_id' => $tashkentId,
            'total_nights' => 7,
            'is_active' => true,
            'status' => 'active',
            'base_currency' => 'USD',
            'margin_percent' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tour_template_stays')->insert([
            ['tour_template_id' => $t1Id, 'city_id' => $istanbulId, 'stay_order' => 1, 'nights' => 2,
             'check_in_date' => null, 'check_out_date' => null,
             'created_at' => now(), 'updated_at' => now()],
            ['tour_template_id' => $t1Id, 'city_id' => $niceId, 'stay_order' => 2, 'nights' => 4,
             'check_in_date' => null, 'check_out_date' => null,
             'created_at' => now(), 'updated_at' => now()],
            ['tour_template_id' => $t1Id, 'city_id' => $istanbulId, 'stay_order' => 3, 'nights' => 1,
             'check_in_date' => null, 'check_out_date' => null,
             'created_at' => now(), 'updated_at' => now()],
        ]);

        // Leg 1: TAS→IST day+0 Centrum Air (local_db)
        $leg1 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t1Id, 'leg_order' => 1,
            'departure_city_id' => $tashkentId, 'arrival_city_id' => $istanbulId,
            'airline_id' => $c2Id, 'day_offset' => 0,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 2: IST→NCE day+2 Turkish Airlines (rapidapi)
        $leg2 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t1Id, 'leg_order' => 2,
            'departure_city_id' => $istanbulId, 'arrival_city_id' => $niceId,
            'airline_id' => $tkId, 'day_offset' => 2,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'rapidapi', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 3: NCE→IST day+6 Turkish Airlines (rapidapi, paired with leg 2)
        $leg3 = DB::table('tour_template_legs')->insertGetId([
            'tour_template_id' => $t1Id, 'leg_order' => 3,
            'departure_city_id' => $niceId, 'arrival_city_id' => $istanbulId,
            'airline_id' => $tkId, 'day_offset' => 6,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'rapidapi', 'round_trip_pair_id' => $leg2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Link leg 2 ↔ leg 3
        DB::table('tour_template_legs')->where('id', $leg2)->update(['round_trip_pair_id' => $leg3]);

        // Leg 4: IST→TAS day+7 Centrum Air (local_db) — after 1n transit in IST
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t1Id, 'leg_order' => 4,
            'departure_city_id' => $istanbulId, 'arrival_city_id' => $tashkentId,
            'airline_id' => $c2Id, 'day_offset' => 7,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->command->info("Created: Istanbul + Nice (ID: {$t1Id}) — 4 legs, 2 stays");

        // ═══════════════════════════════════════════
        // Template 2: Istanbul + Baku
        // TAS→IST(+0) → IST→GYD(+2) → GYD→TAS(+6)
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

        DB::table('tour_template_stays')->insert([
            ['tour_template_id' => $t2Id, 'city_id' => $istanbulId, 'stay_order' => 1, 'nights' => 2,
             'check_in_date' => null, 'check_out_date' => null,
             'created_at' => now(), 'updated_at' => now()],
            ['tour_template_id' => $t2Id, 'city_id' => $bakuId, 'stay_order' => 2, 'nights' => 4,
             'check_in_date' => null, 'check_out_date' => null,
             'created_at' => now(), 'updated_at' => now()],
        ]);

        // Leg 1: TAS→IST day+0 Centrum Air (local_db)
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t2Id, 'leg_order' => 1,
            'departure_city_id' => $tashkentId, 'arrival_city_id' => $istanbulId,
            'airline_id' => $c2Id, 'day_offset' => 0,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 2: IST→GYD day+2 Azerbaijan Airlines (rapidapi)
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t2Id, 'leg_order' => 2,
            'departure_city_id' => $istanbulId, 'arrival_city_id' => $bakuId,
            'airline_id' => $j2Id, 'day_offset' => 2,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'rapidapi', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Leg 3: GYD→TAS day+6 Centrum Air (local_db)
        DB::table('tour_template_legs')->insert([
            'tour_template_id' => $t2Id, 'leg_order' => 3,
            'departure_city_id' => $bakuId, 'arrival_city_id' => $tashkentId,
            'airline_id' => $c2Id, 'day_offset' => 6,
            'preferred_time_range' => 'any', 'passenger_count' => 1,
            'flight_source' => 'local_db', 'round_trip_pair_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->command->info("Created: Istanbul + Baku (ID: {$t2Id}) — 3 legs, 2 stays");
    }
}
