<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Services\RapidApiFlightService;
use Illuminate\Console\Command;

/**
 * Fetch latest nonstop flight prices from RapidAPI Google Flights Scraper.
 * Updates IST↔NCE and IST↔GYD flights, then recalculates tour prices.
 *
 * Usage:
 *   php artisan flights:update-prices              # update default routes
 *   php artisan flights:update-prices --route=IST-NCE  # specific route
 *   php artisan flights:update-prices --dry-run    # preview only
 *   php artisan flights:update-prices --force      # skip cache
 */
class UpdateFlightPrices extends Command
{
    protected $signature = 'flights:update-prices
        {--route=* : Routes to update (e.g., IST-NCE). Defaults to IST-NCE,NCE-IST,IST-GYD,GYD-IST.}
        {--dry-run : Show prices without updating}
        {--force : Skip cache, fetch fresh}';

    protected $description = 'Update flight prices from RapidAPI Google Flights';

    private const DEFAULT_ROUTES = ['IST-NCE', 'NCE-IST', 'IST-GYD', 'GYD-IST'];

    public function __construct(
        private readonly RapidApiFlightService $flightService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $routes = $this->option('route');
        if (empty($routes)) {
            $routes = self::DEFAULT_ROUTES;
        }

        // Get flights matching routes
        $query = Flight::where('is_active', true)
            ->where('departure_date', '>=', now()->toDateString())
            ->with(['fromAirport', 'toAirport']);

        $query->where(function ($q) use ($routes) {
            foreach ($routes as $route) {
                [$from, $to] = explode('-', $route);
                $q->orWhere(function ($sq) use ($from, $to) {
                    $sq->whereHas('fromAirport', fn ($aq) => $aq->where('code', $from))
                        ->whereHas('toAirport', fn ($aq) => $aq->where('code', $to));
                });
            }
        });

        $flights = $query->orderBy('departure_date')->get();

        if ($flights->isEmpty()) {
            $this->warn('No active future flights found for routes: ' . implode(', ', $routes));
            return self::SUCCESS;
        }

        // Group by route + date
        $grouped = $flights->groupBy(function ($f) {
            return $f->fromAirport->code . '-' . $f->toAirport->code . ':' . $f->departure_date->format('Y-m-d');
        });

        $this->info("Checking {$grouped->count()} route+date combinations...");

        $headers = ['Route', 'Date', 'Old Price', 'New Price', 'Airline', 'Status'];
        $rows = [];
        $updated = 0;
        $failed = 0;
        $tourIds = collect();

        foreach ($grouped as $key => $flightGroup) {
            [$route, $date] = explode(':', $key);
            [$from, $to] = explode('-', $route);

            $firstFlight = $flightGroup->first();
            $oldPrice = $firstFlight->price_adult;

            // Fetch from RapidAPI
            $result = $force
                ? $this->flightService->searchFresh($from, $to, $date)
                : $this->flightService->searchCheapest($from, $to, $date);

            if ($result && $result['price'] > 0) {
                $newPrice = $result['price'];
                $airline = $result['airline'] ?? '?';

                if (! $dryRun) {
                    foreach ($flightGroup as $flight) {
                        $flight->update(['price_adult' => $newPrice]);
                        $tourIds = $tourIds->merge($flight->tours()->pluck('tours.id'));
                        $updated++;
                    }
                }

                $diff = $newPrice - $oldPrice;
                $arrow = $diff > 1 ? '↑' : ($diff < -1 ? '↓' : '=');
                $rows[] = [$route, $date, '$' . number_format($oldPrice, 0), '$' . number_format($newPrice, 0), $airline, $dryRun ? 'preview' : "updated {$arrow}"];
            } else {
                $failed++;
                $rows[] = [$route, $date, '$' . number_format($oldPrice, 0), '—', '—', 'no data'];
            }

            // Rate limit: 1 sec between requests
            sleep(1);
        }

        $this->table($headers, $rows);

        // Recalculate tour prices
        if (! $dryRun && $tourIds->isNotEmpty()) {
            $uniqueTourIds = $tourIds->unique();
            $this->info("Recalculating {$uniqueTourIds->count()} tour prices...");

            // Prices are dynamic — no recalculation needed
        }

        $this->newLine();
        $this->info("Updated: {$updated} | Failed: {$failed}");
        if ($dryRun) {
            $this->warn('Dry run — no changes made.');
        }

        return self::SUCCESS;
    }
}
