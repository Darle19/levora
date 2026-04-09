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

            // Build lookup by flight number (numeric part only)
            $offersByNumber = [];
            foreach ($offers as $offer) {
                $num = $this->normalizeFlightNumber($offer->flightNumber);
                $offersByNumber[$num] = $offer;
            }

            foreach ($groupFlights as $flight) {
                $num = $this->normalizeFlightNumber($flight->flight_number);
                $offer = $offersByNumber[$num] ?? null;

                if (! $offer) {
                    $this->warn("    {$airlineCode} {$flight->flight_number} — not found");
                    $failed++;
                    continue;
                }

                $depTime = $offer->departureAt->format('H:i:s');
                $arrTime = $offer->arrivalAt->format('H:i:s');
                $arrDate = $offer->arrivalAt->format('Y-m-d');
                $price = $offer->priceCents / 100;

                $changes = [];
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
                    'departure_time' => $depTime,
                    'arrival_time' => $arrTime,
                    'arrival_date' => $arrDate,
                    'price_adult' => $price,
                ]);

                if (! empty($changes)) {
                    $this->info("    {$airlineCode} {$flight->flight_number}: " . implode(', ', $changes));
                    $updated++;
                } else {
                    $this->line("    {$airlineCode} {$flight->flight_number}: no changes");
                }
            }

            // Rate limit — avoid hammering the API
            usleep(500_000);
        }

        $this->info("Done. Updated: {$updated}, Failed: {$failed}, Total: {$flights->count()}");

        Log::info('flights:refresh completed', compact('updated', 'failed'));

        return self::SUCCESS;
    }

    /**
     * Extract numeric part from flight number.
     * "TK 1813" → "1813", "501" → "501", "J2 76" → "76"
     */
    private function normalizeFlightNumber(string $flightNumber): string
    {
        $trimmed = trim($flightNumber);

        // If contains a space, take everything after the last space
        // "TK 1813" → "1813", "J2 76" → "76"
        if (str_contains($trimmed, ' ')) {
            $trimmed = substr($trimmed, strrpos($trimmed, ' ') + 1);
        }

        // If starts with letters, strip them: "TK1813" → "1813"
        $trimmed = preg_replace('/^[A-Z]+/i', '', $trimmed);

        return $trimmed ?: $flightNumber;
    }
}
