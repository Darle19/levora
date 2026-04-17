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
            $result = $generator->generateForWindow($template, $from, $to);
            $rows[] = [
                $template->id,
                $template->route_name,
                $result['dates'],
                $result['created'],
                $result['skipped'],
                empty($result['reasons']) ? '' : count($result['reasons']) . ' dates w/o combos',
            ];
        }

        $this->table(['Template', 'Route', 'Dates', 'Created', 'Skipped', 'Notes'], $rows);
        return self::SUCCESS;
    }
}
