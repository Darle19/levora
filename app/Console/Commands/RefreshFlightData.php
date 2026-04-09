<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Services\Flights\RapidApiFlightProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshFlightData extends Command
{
    protected $signature = 'flights:refresh {--days=30 : Refresh flights within N days from now}';
    protected $description = 'Refresh flight times and prices from RapidAPI for upcoming flights';

    /** Airlines not available in Google Flights (charters, etc.) */
    private const SKIP_AIRLINES = ['C2']; // Centrum Air

    /** Only consider flights departing in this window */
    private const DEP_TIME_MIN = '09:00';
    private const DEP_TIME_MAX = '16:00';

    public function handle(RapidApiFlightProvider $provider): int
    {
        $days = (int) $this->option('days');
        $from = now();
        $to = now()->addDays($days);

        $flights = Flight::with(['airline', 'fromAirport', 'toAirport'])
            ->where('is_active', true)
            ->whereBetween('departure_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereHas('airline', function ($q) {
                $q->whereNotIn('code', self::SKIP_AIRLINES);
            })
            ->orderBy('departure_date')
            ->get();

        if ($flights->isEmpty()) {
            $this->info('No eligible flights to refresh.');
            return self::SUCCESS;
        }

        // Group by route+date+airline to minimize API calls
        $groups = $flights->groupBy(function (Flight $f) {
            return $f->fromAirport->code . '-' . $f->toAirport->code . '-' . $f->departure_date->format('Y-m-d') . '-' . $f->airline->code;
        });

        $this->info("Refreshing {$flights->count()} flights in {$groups->count()} API calls...");

        $updated = 0;
        $failed = 0;

        foreach ($groups as $key => $groupFlights) {
            $sample = $groupFlights->first();
            $origin = $sample->fromAirport->code;
            $destination = $sample->toAirport->code;
            $date = $sample->departure_date->format('Y-m-d');
            $airlineCode = $sample->airline->code;

            $this->line("  {$origin}→{$destination} {$date} [{$airlineCode}]...");

            $offers = $provider->search($origin, $destination, $date, 1, $airlineCode);

            if (empty($offers)) {
                $this->warn("    No results from API.");
                $failed += $groupFlights->count();
                continue;
            }

            // Filter: daytime flights only (09:00–16:00), then pick cheapest
            $daytimeOffers = array_filter($offers, function ($offer) {
                $depHour = $offer->departureAt->format('H:i');
                return $depHour >= self::DEP_TIME_MIN && $depHour <= self::DEP_TIME_MAX;
            });

            if (empty($daytimeOffers)) {
                $this->warn("    No daytime flights (09:00–16:00).");
                $failed += $groupFlights->count();
                continue;
            }

            // Sort by price, pick cheapest
            usort($daytimeOffers, fn ($a, $b) => $a->priceCents <=> $b->priceCents);
            $best = $daytimeOffers[0];

            $depTime = $best->departureAt->format('H:i:s');
            $arrTime = $best->arrivalAt->format('H:i:s');
            $arrDate = $best->arrivalAt->format('Y-m-d');
            $price = $best->priceCents / 100;
            $bestNum = $best->flightNumber;

            // Update all flights in this group with the best daytime offer
            foreach ($groupFlights as $flight) {
                $changes = [];
                if ($flight->flight_number !== $bestNum) {
                    $changes[] = "flight: {$flight->flight_number}→{$bestNum}";
                }
                if ($flight->departure_time !== $depTime) {
                    $changes[] = "dep: {$flight->departure_time}→{$depTime}";
                }
                if ($flight->arrival_time !== $arrTime) {
                    $changes[] = "arr: {$flight->arrival_time}→{$arrTime}";
                }
                if ((float) $flight->price_adult !== $price) {
                    $changes[] = "price: {$flight->price_adult}→{$price}";
                }

                $flight->update([
                    'flight_number' => $bestNum,
                    'departure_time' => $depTime,
                    'arrival_time' => $arrTime,
                    'arrival_date' => $arrDate,
                    'price_adult' => $price,
                ]);

                if (! empty($changes)) {
                    $this->info("    {$airlineCode} {$bestNum}: " . implode(', ', $changes));
                    $updated++;
                } else {
                    $this->line("    {$airlineCode} {$bestNum}: no changes");
                }
            }

            // Rate limit
            usleep(500_000);
        }

        $this->info("Done. Updated: {$updated}, Failed: {$failed}, Total: {$flights->count()}");

        Log::info('flights:refresh completed', compact('updated', 'failed'));

        return self::SUCCESS;
    }
}
