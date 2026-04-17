<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\FlightPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove duplicate FlightPaths: same departure_date + identical flight_id set.
 *
 * For each duplicate cluster, the oldest FlightPath (lowest id) is kept; the
 * rest are deleted. FlightPaths that already have bookings attached are NEVER
 * deleted — they stay even if they are the younger duplicate, and they report
 * an error so ops can resolve manually.
 *
 * Emits a report table so a --dry-run is effectively part of every call.
 */
class DedupeFlightPaths extends Command
{
    protected $signature = 'tours:dedupe-paths {--apply : Actually delete. Without this flag, only reports what would happen.}';

    protected $description = 'Remove duplicate FlightPaths with identical date + flight legs, keeping the oldest.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $paths = FlightPath::with([
            'legs' => fn ($q) => $q->orderBy('leg_order'),
            'legs.flight:id,airline_id,from_airport_id,to_airport_id,departure_date',
        ])
            ->orderBy('departure_date')
            ->orderBy('id')
            ->get();

        $seen = []; // key: "date|airline+route signature" → first fp_id
        $duplicates = []; // [fp_id => original_fp_id]

        // Two FlightPaths are duplicates when, leg-for-leg, they carry the
        // same airline on the same segment on the same date. Comparing by
        // flight_id misses cases where the flights table has two rows for
        // the same real-world segment (e.g. RapidAPI stored a new row with
        // a slightly different flight_number).
        foreach ($paths as $fp) {
            $sig = $fp->legs->map(function ($l) {
                if (! $l->flight) {
                    return 'null';
                }
                $date = $l->flight->departure_date instanceof \Carbon\CarbonInterface
                    ? $l->flight->departure_date->toDateString()
                    : (string) $l->flight->departure_date;
                return $l->leg_order . ':' . $l->flight->airline_id . ':' . $l->flight->from_airport_id . ':' . $l->flight->to_airport_id . ':' . $date;
            })->implode('|');

            $key = $fp->departure_date->toDateString() . '|' . $sig;

            if (isset($seen[$key])) {
                $duplicates[$fp->id] = $seen[$key];
            } else {
                $seen[$key] = $fp->id;
            }
        }

        if (empty($duplicates)) {
            $this->info('No duplicate FlightPaths found.');
            return self::SUCCESS;
        }

        $dupIds = array_keys($duplicates);
        $withBookings = Booking::whereIn('bookable_id', $dupIds)
            ->where('bookable_type', FlightPath::class)
            ->pluck('bookable_id')
            ->unique()
            ->all();

        $rows = [];
        $safeToDelete = [];
        foreach ($duplicates as $dupId => $originalId) {
            $hasBooking = in_array($dupId, $withBookings, true);
            $rows[] = [$dupId, $originalId, $hasBooking ? 'HAS BOOKINGS — skipped' : ($apply ? 'deleted' : 'would delete')];
            if (! $hasBooking) {
                $safeToDelete[] = $dupId;
            }
        }

        $this->table(['Dup FP', 'Keep FP', 'Action'], $rows);

        if (! $apply) {
            $this->warn('Dry run. Re-run with --apply to actually delete.');
            return self::SUCCESS;
        }

        if (empty($safeToDelete)) {
            $this->warn('Nothing safe to delete.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($safeToDelete) {
            // flight_path_legs and flight_path_stays cascade-delete on FK.
            FlightPath::whereIn('id', $safeToDelete)->delete();
        });

        $this->info('Deleted ' . count($safeToDelete) . ' duplicate FlightPath(s).');

        return self::SUCCESS;
    }
}
