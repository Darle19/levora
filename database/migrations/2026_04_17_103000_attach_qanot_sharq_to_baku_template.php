<?php

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Currency;
use App\Models\TourTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wire Qanot Sharq (HH) into the Istanbul+Baku tour template:
 *
 * 1. Seed HH GYD→TAS return flights parallel to the existing TAS→IST outbound
 *    schedule (same dates + 7 days).
 * 2. Attach HH to the template's outbound (leg 1, TAS→IST) and return
 *    (leg 3, GYD→TAS) legs via tour_template_leg_airlines.
 * 3. Link leg 1 ↔ leg 3 via round_trip_pair_id so the generator requires
 *    the same airline on both ends of the trip (block-seat rule).
 *
 * After this migration, `php artisan tours:generate-paths` will emit a new
 * Qanot-flavoured FlightPath per matching base date, in parallel with the
 * existing Centrum paths. Existing paths are untouched.
 *
 * Safe to run only after:
 *   - tours:backfill-templates has populated TourTemplate "Istanbul + Baku"
 *   - Qanot Sharq (HH) seed migration has run
 * If either precondition is missing, the migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $airline = Airline::where('code', 'HH')->first();
        $template = TourTemplate::where('route_name', 'Istanbul + Baku')->with('legs')->first();
        if (! $airline || ! $template) {
            return; // preconditions missing; re-run after backfill + HH seed
        }

        $gyd = Airport::where('code', 'GYD')->first();
        $tas = Airport::where('code', 'TAS')->first();
        $usd = Currency::where('code', 'USD')->first();

        // 1. Seed HH GYD→TAS return flights (7 days after each TAS→IST departure).
        // Raw DB::table to keep date columns stored as plain 'YYYY-MM-DD' strings
        // (Eloquent's date cast would rewrite them as datetimes and break
        // equality lookups elsewhere in the schema — the rest of the flights
        // table is date-only).
        if ($gyd && $tas && $usd) {
            $outboundDates = ['2026-04-27', '2026-05-04', '2026-05-11', '2026-05-18', '2026-05-25',
                              '2026-06-01', '2026-06-08', '2026-06-15', '2026-06-22', '2026-06-29'];
            foreach ($outboundDates as $dep) {
                $ret = date('Y-m-d', strtotime($dep . ' +7 days'));
                $exists = DB::table('flights')
                    ->where('airline_id', $airline->id)
                    ->where('flight_number', 'HH 7502')
                    ->where('departure_date', $ret)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('flights')->insert([
                    'airline_id' => $airline->id,
                    'flight_number' => 'HH 7502',
                    'departure_date' => $ret,
                    'from_airport_id' => $gyd->id,
                    'to_airport_id' => $tas->id,
                    'origin_city_id' => $gyd->city_id,
                    'destination_city_id' => $tas->city_id,
                    'departure_time' => '14:00:00',
                    'arrival_date' => $ret,
                    'arrival_time' => '17:00:00',
                    'price_adult' => 350,
                    'price_child' => 350,
                    'price_infant' => 0,
                    'currency_id' => $usd->id,
                    'available_seats' => 20,
                    'class_type' => 'economy',
                    'soft_block_price' => 350,
                    'soft_block_release_days' => 14,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Attach HH to template legs 1 (outbound) and 3 (return).
        $legs = $template->legs->keyBy('leg_order');
        $legOut = $legs->get(1);
        $legRet = $legs->get(3);
        if ($legOut) {
            $legOut->airlines()->syncWithoutDetaching([$airline->id]);
        }
        if ($legRet) {
            $legRet->airlines()->syncWithoutDetaching([$airline->id]);
        }

        // 3. Pair outbound ↔ return so the generator enforces same-airline round-trip.
        if ($legOut && $legRet) {
            if (! $legOut->round_trip_pair_id) {
                $legOut->update(['round_trip_pair_id' => $legRet->id]);
            }
            if (! $legRet->round_trip_pair_id) {
                $legRet->update(['round_trip_pair_id' => $legOut->id]);
            }
        }
    }

    public function down(): void
    {
        $airline = Airline::where('code', 'HH')->first();
        $template = TourTemplate::where('route_name', 'Istanbul + Baku')->with('legs')->first();

        if ($airline && $template) {
            foreach ($template->legs as $leg) {
                $leg->airlines()->detach($airline->id);
            }

            $legs = $template->legs->keyBy('leg_order');
            foreach ([$legs->get(1), $legs->get(3)] as $leg) {
                if ($leg && $leg->round_trip_pair_id) {
                    $leg->update(['round_trip_pair_id' => null]);
                }
            }
        }

        if ($airline) {
            DB::table('flights')
                ->where('airline_id', $airline->id)
                ->where('flight_number', 'HH 7502')
                ->delete();
        }
    }
};
