<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Services\GoogleFlightsService;
use App\Services\TourPricingService;
use Illuminate\Console\Command;

/**
 * Fetch latest nonstop flight prices from Google Flights (economy + business).
 * Updates local flight records and recalculates tour prices.
 *
 * Usage:
 *   php artisan flights:update-prices                    # update all active routes
 *   php artisan flights:update-prices --route=IST-NCE    # specific route
 *   php artisan flights:update-prices --dry-run           # preview only
 *   php artisan flights:update-prices --force             # skip cache
 */
class UpdateFlightPrices extends Command
{
    protected $signature = 'flights:update-prices
        {--route=* : Routes to update. Defaults to IST-NCE, NCE-IST, IST-GYD, GYD-IST.}
        {--dry-run : Show prices without updating}
        {--force : Skip cache, fetch fresh}';

    protected $description = 'Update IST↔NCE and IST↔GYD flight prices from Google Flights';

    /** Routes to update by default (outbound only, return prices set manually) */
    private const DEFAULT_ROUTES = ['IST-NCE', 'IST-GYD'];

    public function __construct(
        private readonly GoogleFlightsService $googleFlights,
        private readonly TourPricingService $pricingService,
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
            $this->warn('No active future flights found.');
            return self::SUCCESS;
        }

        // Group by route + date
        $grouped = $flights->groupBy(function ($f) {
            return $f->fromAirport->code . '-' . $f->toAirport->code . ':' . $f->departure_date->format('Y-m-d');
        });

        $this->info("Checking {$grouped->count()} route+date combinations...");
        $this->newLine();

        $headers = ['Route', 'Date', 'Old Price', 'Economy', 'Business', 'Status'];
        $rows = [];
        $updated = 0;
        $failed = 0;
        $tourIds = collect();

        foreach ($grouped as $key => $flightGroup) {
            [$route, $date] = explode(':', $key);
            [$from, $to] = explode('-', $route);

            $firstFlight = $flightGroup->first();
            $oldPrice = $firstFlight->price_adult;

            // Fetch both classes
            $result = $force
                ? $this->googleFlights->searchAllClassesFresh($from, $to, $date)
                : $this->googleFlights->searchAllClasses($from, $to, $date);

            $econPrice = $result['economy'];
            $bizPrice = $result['business'];
            $status = '—';

            if ($econPrice && ! $dryRun) {
                foreach ($flightGroup as $flight) {
                    $flight->update([
                        'price_adult' => $econPrice,
                        'soft_block_price' => $econPrice,
                        'hard_block_price' => $bizPrice ?? $econPrice,
                    ]);
                    $tourIds = $tourIds->merge($flight->tours()->pluck('tours.id'));
                    $updated++;
                }
                $diff = $econPrice - $oldPrice;
                $arrow = $diff > 0 ? '↑' : ($diff < 0 ? '↓' : '=');
                $status = $dryRun ? 'preview' : "updated {$arrow}";
            } elseif ($econPrice && $dryRun) {
                $status = 'preview';
            } else {
                $failed++;
                $status = 'no data';
            }

            $rows[] = [
                $route,
                $date,
                '$' . number_format($oldPrice, 0),
                $econPrice ? '$' . number_format($econPrice, 0) : '—',
                $bizPrice ? '$' . number_format($bizPrice, 0) : '—',
                $status,
            ];

            // Rate limit: 1 sec between route+date combos
            sleep(1);
        }

        $this->table($headers, $rows);

        // Recalculate tour prices
        if (! $dryRun && $tourIds->isNotEmpty()) {
            $uniqueTourIds = $tourIds->unique();
            $this->info("Recalculating {$uniqueTourIds->count()} tour prices...");

            foreach ($uniqueTourIds as $tourId) {
                $tour = \App\Models\Tour::find($tourId);
                if ($tour) {
                    $this->pricingService->recalculate($tour);
                }
            }
        }

        $this->newLine();
        $this->info("Updated: {$updated} | Failed: {$failed}");

        if ($dryRun) {
            $this->warn('Dry run — no changes made. Remove --dry-run to update.');
        }

        return self::SUCCESS;
    }
}
