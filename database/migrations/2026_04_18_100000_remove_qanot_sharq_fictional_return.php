<?php

use App\Models\Airline;
use App\Models\TourTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Undo the "Qanot Sharq round-trip" assumption.
 *
 * A previous migration seeded HH 7502 GYD→TAS as a return leg and paired
 * it with the outbound HH 7501, so the path generator produced all-HH
 * combos for Istanbul+Baku. Ops confirmed Qanot Sharq only operates
 * TAS→IST — there is no HH return — so those rows don't reflect reality.
 *
 * This migration:
 *  1. Deletes FlightPath rows that reference any HH 7502 leg (and their
 *     child leg/stay rows via cascade).
 *  2. Deletes the fictional HH 7502 Flight rows.
 *  3. Detaches HH from the Istanbul+Baku template's return leg (leg 3,
 *     GYD→TAS) so future generation runs don't pair it.
 *  4. Clears round_trip_pair_id between legs 1 and 3 of that template —
 *     there is no block-seat pair constraint to enforce when only the
 *     outbound side has HH.
 *
 * After running, `php artisan tours:generate-paths` will produce valid
 * HH+J2+C2 combos (Qanot outbound + Azerbaijan middle + Centrum return)
 * alongside the existing all-Centrum paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        $hh = Airline::where('code', 'HH')->first();
        if (! $hh) {
            return;
        }

        // Find HH 7502 flights so we can remove any FlightPath that references them.
        $returnFlightIds = DB::table('flights')
            ->where('airline_id', $hh->id)
            ->where('flight_number', 'HH 7502')
            ->pluck('id')
            ->all();

        if (! empty($returnFlightIds)) {
            $affectedPaths = DB::table('flight_path_legs')
                ->whereIn('flight_id', $returnFlightIds)
                ->pluck('flight_path_id')
                ->unique()
                ->all();

            if (! empty($affectedPaths)) {
                // flight_path_legs + flight_path_stays cascade off flight_paths.
                DB::table('flight_paths')->whereIn('id', $affectedPaths)->delete();
            }

            DB::table('flights')->whereIn('id', $returnFlightIds)->delete();
        }

        $template = TourTemplate::where('route_name', 'Istanbul + Baku')->with('legs')->first();
        if (! $template) {
            return;
        }

        $legs = $template->legs->keyBy('leg_order');
        $leg1 = $legs->get(1);
        $leg3 = $legs->get(3);

        if ($leg3) {
            $leg3->airlines()->detach($hh->id);
        }

        foreach ([$leg1, $leg3] as $leg) {
            if ($leg && $leg->round_trip_pair_id) {
                $leg->update(['round_trip_pair_id' => null]);
            }
        }
    }

    public function down(): void
    {
        // Reinstating fictional return flights would recreate the bug; leave
        // down() as a no-op. If the data is ever needed back, re-run the
        // original attach_qanot_sharq_to_baku_template migration manually.
    }
};
