<?php

namespace App\Console\Commands;

use App\Models\TourTemplate;
use App\Services\Flights\FlightPathGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Generate FlightPaths from TourTemplates over a date window.
 *
 * Base dates come from the template's first leg (day_offset=0). In practice
 * that means "every Flight departing on the template-leg-1's route within the
 * window is a candidate base date". We skip combos already represented in DB
 * so re-runs never duplicate existing FlightPaths.
 */
class GenerateFlightPaths extends Command
{
    protected $signature = 'tours:generate-paths
        {--template= : Specific TourTemplate id (default: all active)}
        {--from= : Earliest base date YYYY-MM-DD (default: today)}
        {--to= : Latest base date YYYY-MM-DD (default: today+90d)}';

    protected $description = 'Generate FlightPaths from TourTemplates for each valid airline combo';

    public function handle(FlightPathGenerator $generator): int
    {
        $from = $this->option('from') ? CarbonImmutable::parse($this->option('from')) : CarbonImmutable::today();
        $to = $this->option('to') ? CarbonImmutable::parse($this->option('to')) : CarbonImmutable::today()->addDays(90);

        $query = TourTemplate::where('is_active', true)->with(['legs.airlines', 'stays']);
        if ($this->option('template')) {
            $query->where('id', $this->option('template'));
        }
        $templates = $query->get();

        if ($templates->isEmpty()) {
            $this->warn('No active templates matched.');
            return self::SUCCESS;
        }

        $this->info("Generating FlightPaths for {$templates->count()} template(s), window {$from->toDateString()} .. {$to->toDateString()}");

        $rows = [];
        foreach ($templates as $template) {
            $created = 0;
            $skipped = 0;
            $reasons = [];

            $baseDates = $this->candidateBaseDates($template, $from, $to);
            foreach ($baseDates as $baseDate) {
                $result = $generator->generate($template, $baseDate);
                $created += $result['created'];
                $skipped += $result['skipped'];
                if (! empty($result['reason']) && $result['created'] === 0 && $result['skipped'] === 0) {
                    $reasons[$baseDate->toDateString()] = $result['reason'];
                }
            }

            $rows[] = [
                $template->id,
                $template->route_name,
                count($baseDates),
                $created,
                $skipped,
                empty($reasons) ? '' : count($reasons) . ' dates w/o combos',
            ];
        }

        $this->table(['Template', 'Route', 'Dates', 'Created', 'Skipped', 'Notes'], $rows);
        return self::SUCCESS;
    }

    /**
     * Base dates = any date in [$from, $to] on which at least one flight matches the
     * template's first leg (day_offset=0). Other legs are checked during generation.
     * Returns CarbonImmutable[].
     *
     * @return array<int,CarbonImmutable>
     */
    private function candidateBaseDates(TourTemplate $template, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $firstLeg = $template->legs->sortBy('leg_order')->first();
        if (! $firstLeg) {
            return [];
        }

        $airlineIds = $firstLeg->airlines->pluck('id')->all();
        if (empty($airlineIds)) {
            return [];
        }

        $dates = \App\Models\Flight::query()
            ->where('is_active', true)
            ->whereIn('airline_id', $airlineIds)
            ->whereBetween('departure_date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('fromAirport', fn ($q) => $q->where('city_id', $firstLeg->departure_city_id))
            ->whereHas('toAirport', fn ($q) => $q->where('city_id', $firstLeg->arrival_city_id))
            ->pluck('departure_date')
            ->map(fn ($d) => CarbonImmutable::parse($d))
            ->unique(fn ($d) => $d->toDateString())
            ->values()
            ->all();

        return $dates;
    }
}
