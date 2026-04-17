<?php

namespace App\Console\Commands;

use App\Models\FlightPath;
use App\Models\TourTemplate;
use App\Models\TourTemplateLeg;
use App\Models\TourTemplateStay;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reverse-engineer TourTemplates from the 24 FlightPaths that exist today.
 *
 * For every FlightPath we create (once, keyed by route_name) a TourTemplate,
 * its legs (by departure/arrival city + leg_order) and stays. Each template
 * leg gets the airline from the existing FP leg attached via the pivot.
 * round_trip_pair_id is set when two legs of the same airline form a mirror
 * pair within the same FP.
 *
 * Finally, flight_paths.tour_template_id is filled in so the generator can
 * recognise existing paths and skip re-creating them.
 *
 * Idempotent: firstOrCreate + syncWithoutDetaching. Safe to re-run.
 */
class BackfillTourTemplates extends Command
{
    protected $signature = 'tours:backfill-templates {--dry-run}';

    protected $description = 'Create TourTemplates from existing FlightPaths without modifying the paths themselves';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $paths = FlightPath::with(['legs.flight.fromAirport', 'legs.flight.toAirport', 'stays'])->get();
        if ($paths->isEmpty()) {
            $this->warn('No FlightPaths — nothing to backfill.');
            return self::SUCCESS;
        }

        $templatesCreated = 0;
        $legsAttached = 0;
        $pathsLinked = 0;

        DB::beginTransaction();
        try {
            foreach ($paths as $fp) {
                $template = TourTemplate::firstOrCreate(
                    ['route_name' => $fp->route_name],
                    [
                        'departure_city_id' => $fp->departure_city_id,
                        'total_nights' => $fp->nights,
                        'is_active' => true,
                        'status' => 'active',
                        'base_currency' => 'USD',
                        'margin_percent' => 0,
                    ]
                );
                if ($template->wasRecentlyCreated) {
                    $templatesCreated++;
                }

                foreach ($fp->stays as $stay) {
                    TourTemplateStay::firstOrCreate(
                        ['tour_template_id' => $template->id, 'stay_order' => $stay->stay_order],
                        ['city_id' => $stay->city_id, 'nights' => $stay->nights]
                    );
                }

                $fpBaseDate = CarbonImmutable::parse($fp->departure_date);
                $legsByOrder = [];

                foreach ($fp->legs as $fpLeg) {
                    $flight = $fpLeg->flight;
                    if (! $flight) {
                        continue;
                    }
                    $fromCityId = $flight->fromAirport?->city_id;
                    $toCityId = $flight->toAirport?->city_id;
                    if (! $fromCityId || ! $toCityId) {
                        continue;
                    }

                    $dayOffset = $fpBaseDate->diffInDays(CarbonImmutable::parse($flight->departure_date), false);

                    $templateLeg = TourTemplateLeg::firstOrCreate(
                        ['tour_template_id' => $template->id, 'leg_order' => $fpLeg->leg_order],
                        [
                            'departure_city_id' => $fromCityId,
                            'arrival_city_id' => $toCityId,
                            'day_offset' => (int) $dayOffset,
                            'preferred_time_range' => 'any',
                            'passenger_count' => 1,
                            'flight_source' => 'direct',
                        ]
                    );

                    $templateLeg->airlines()->syncWithoutDetaching([$flight->airline_id]);
                    $legsAttached++;

                    $legsByOrder[$fpLeg->leg_order] = [
                        'template_leg' => $templateLeg,
                        'from_airport_id' => $flight->from_airport_id,
                        'to_airport_id' => $flight->to_airport_id,
                        'airline_id' => $flight->airline_id,
                    ];
                }

                // Pair outbound↔return legs that mirror each other and share an airline
                foreach ($legsByOrder as $a) {
                    foreach ($legsByOrder as $b) {
                        if ($a['template_leg']->id === $b['template_leg']->id) {
                            continue;
                        }
                        if ($a['airline_id'] !== $b['airline_id']) {
                            continue;
                        }
                        if ($a['from_airport_id'] === $b['to_airport_id']
                            && $a['to_airport_id'] === $b['from_airport_id']
                            && $a['template_leg']->round_trip_pair_id === null) {
                            $a['template_leg']->update(['round_trip_pair_id' => $b['template_leg']->id]);
                        }
                    }
                }

                if ($fp->tour_template_id !== $template->id) {
                    $fp->update(['tour_template_id' => $template->id]);
                    $pathsLinked++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry run — rolled back.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Backfill failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['Templates created', 'Airlines attached', 'Paths linked'],
            [[$templatesCreated, $legsAttached, $pathsLinked]]
        );

        return self::SUCCESS;
    }
}
