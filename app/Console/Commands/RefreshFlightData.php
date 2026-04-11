<?php

namespace App\Console\Commands;

use App\Models\Flight;
use App\Models\FlightPath;
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
    private const DEP_TIME_MIN = '04:00';
    private const DEP_TIME_MAX = '17:00';

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
            ->get()
            ->keyBy('id');

        if ($flights->isEmpty()) {
            $this->info('No eligible flights to refresh.');
            return self::SUCCESS;
        }

        // Build RT pairs from FlightPaths: [outbound_flight_id => return_flight_id]
        $rtPairs = $this->detectRoundTripPairs($flights->pluck('id')->all());

        $updated = 0;
        $failed = 0;

        // Group flights by route+date+airline (shared between RT and OW logic)
        $groupKey = fn (Flight $f) => $f->fromAirport->code . '-' . $f->toAirport->code . '-' . $f->departure_date->format('Y-m-d') . '-' . $f->airline->code;
        $allGroups = $flights->groupBy($groupKey);

        // Build set of group keys that are part of RT pairs
        $rtGroupKeys = [];
        $processedRtGroups = [];

        foreach ($rtPairs as $outboundId => $returnId) {
            $outbound = $flights->get($outboundId);
            $return = $flights->get($returnId);
            if (! $outbound || ! $return) {
                continue;
            }
            $outKey = $groupKey($outbound);
            $retKey = $groupKey($return);
            $rtGroupKeys[$outKey] = $retKey;
        }

        // Process RT group pairs
        foreach ($rtGroupKeys as $outKey => $retKey) {
            if (in_array($outKey, $processedRtGroups) || in_array($retKey, $processedRtGroups)) {
                continue;
            }
            $outboundFlights = $allGroups->get($outKey, collect());
            $returnFlights = $allGroups->get($retKey, collect());
            if ($outboundFlights->isEmpty() || $returnFlights->isEmpty()) {
                continue;
            }

            $result = $this->processRoundTripGroup($outboundFlights, $returnFlights, $provider);
            $updated += $result['updated'];
            $failed += $result['failed'];
            $processedRtGroups[] = $outKey;
            $processedRtGroups[] = $retKey;
        }

        // Process remaining groups as one-way (exclude RT-processed groups)
        $owGroups = $allGroups->reject(fn ($_, $key) => in_array($key, $processedRtGroups));

        $rtFlightCount = $flights->count() - $owGroups->flatten()->count();
        $this->info("Refreshing {$rtFlightCount} flights in " . (count($processedRtGroups) / 2) . " RT pair(s) + " . $owGroups->flatten()->count() . " flights in " . $owGroups->count() . " one-way call(s)...");

        foreach ($owGroups as $groupFlights) {
            $result = $this->processOneWayGroup($groupFlights, $provider);
            $updated += $result['updated'];
            $failed += $result['failed'];
            usleep(500_000);
        }

        $this->info("Done. Updated: {$updated}, Failed: {$failed}, Total: {$flights->count()}");
        Log::info('flights:refresh completed', compact('updated', 'failed'));

        return self::SUCCESS;
    }

    /**
     * Detect round-trip pairs by scanning FlightPaths.
     * Returns [outbound_flight_id => return_flight_id].
     */
    private function detectRoundTripPairs(array $flightIds): array
    {
        $pairs = [];

        $flightPaths = FlightPath::whereHas('legs', function ($q) use ($flightIds) {
                $q->whereIn('flight_id', $flightIds);
            })
            ->with(['legs.flight' => fn ($q) => $q->with('airline')])
            ->get();

        foreach ($flightPaths as $fp) {
            // Group legs by airline
            $byAirline = $fp->legs
                ->filter(fn ($l) => $l->flight && $l->flight->airline)
                ->groupBy(fn ($l) => $l->flight->airline_id);

            foreach ($byAirline as $airlineLegs) {
                if ($airlineLegs->count() !== 2) {
                    continue;
                }
                $sorted = $airlineLegs->sortBy('leg_order')->values();
                $a = $sorted[0]->flight;
                $b = $sorted[1]->flight;

                // Check reversed airports (a→b then b→a)
                if ($a->from_airport_id === $b->to_airport_id
                    && $a->to_airport_id === $b->from_airport_id) {
                    $pairs[$a->id] = $b->id;
                }
            }
        }

        return $pairs;
    }

    /**
     * Process a round-trip group pair: apply RT pricing to ALL flights in both groups.
     */
    private function processRoundTripGroup($outboundFlights, $returnFlights, RapidApiFlightProvider $provider): array
    {
        $sampleOut = $outboundFlights->first();
        $sampleRet = $returnFlights->first();
        $origin = $sampleOut->fromAirport->code;
        $destination = $sampleOut->toAirport->code;
        $depDate = $sampleOut->departure_date->format('Y-m-d');
        $retDate = $sampleRet->departure_date->format('Y-m-d');
        $airlineCode = $sampleOut->airline->code;

        $this->line("  RT: {$origin}↔{$destination} {$depDate}/{$retDate} [{$airlineCode}]...");

        // Single RT call: returns outbound options, each with RT total price
        $offers = $provider->searchRoundTripOutbound($origin, $destination, $depDate, $retDate, 1, $airlineCode);

        if (empty($offers)) {
            $this->warn("    RT API empty, falling back to one-way.");
            $r1 = $this->processOneWayGroup($outboundFlights, $provider);
            $r2 = $this->processOneWayGroup($returnFlights, $provider);
            return ['updated' => $r1['updated'] + $r2['updated'], 'failed' => $r1['failed'] + $r2['failed']];
        }

        $best = $this->pickCheapestDaytime($offers);
        if (! $best) {
            $this->warn("    No daytime flights in RT result.");
            return ['updated' => 0, 'failed' => $outboundFlights->count() + $returnFlights->count()];
        }

        // $best->priceCents is the RT total (full round trip for 1 adult).
        // Split evenly: half on outbound, half on return.
        $rtTotalCents = $best->priceCents;
        $halfPrice = round($rtTotalCents / 2 / 100, 2);

        // Update all outbound flights: time + number from API, price = half
        foreach ($outboundFlights as $f) {
            $f->update([
                'flight_number' => $best->flightNumber,
                'departure_time' => $best->departureAt->format('H:i:s'),
                'arrival_time' => $best->arrivalAt->format('H:i:s'),
                'arrival_date' => $best->arrivalAt->format('Y-m-d'),
                'price_adult' => $halfPrice,
            ]);
        }

        // Update all return flights: only price (times/number unchanged)
        foreach ($returnFlights as $f) {
            $f->update(['price_adult' => $halfPrice]);
        }

        $count = $outboundFlights->count() + $returnFlights->count();
        $this->info("    ✓ {$airlineCode} RT total: \$" . ($rtTotalCents / 100) . " (split \$" . $halfPrice . " per leg, {$count} flight(s) updated)");

        return ['updated' => $count, 'failed' => 0];
    }

    private function processOneWayGroup($groupFlights, RapidApiFlightProvider $provider): array
    {
        $sample = $groupFlights->first();
        $origin = $sample->fromAirport->code;
        $destination = $sample->toAirport->code;
        $date = $sample->departure_date->format('Y-m-d');
        $airlineCode = $sample->airline->code;

        $this->line("  OW: {$origin}→{$destination} {$date} [{$airlineCode}]...");

        $offers = $provider->search($origin, $destination, $date, 1, $airlineCode);

        if (empty($offers)) {
            $this->warn("    No results from API.");
            return ['updated' => 0, 'failed' => $groupFlights->count()];
        }

        $best = $this->pickCheapestDaytime($offers);
        if (! $best) {
            $this->warn("    No daytime flights (" . self::DEP_TIME_MIN . "–" . self::DEP_TIME_MAX . ").");
            return ['updated' => 0, 'failed' => $groupFlights->count()];
        }

        $updated = 0;
        foreach ($groupFlights as $flight) {
            if ($this->updateFlight($flight, $best)) {
                $updated++;
            }
        }

        return ['updated' => $updated, 'failed' => 0];
    }

    private function pickCheapestDaytime(array $offers)
    {
        $daytime = array_filter($offers, function ($offer) {
            $depHour = $offer->departureAt->format('H:i');
            return $depHour >= self::DEP_TIME_MIN && $depHour <= self::DEP_TIME_MAX;
        });

        if (empty($daytime)) {
            return null;
        }

        usort($daytime, fn ($a, $b) => $a->priceCents <=> $b->priceCents);
        return $daytime[0];
    }

    private function updateFlight(Flight $flight, $offer): bool
    {
        $depTime = $offer->departureAt->format('H:i:s');
        $arrTime = $offer->arrivalAt->format('H:i:s');
        $arrDate = $offer->arrivalAt->format('Y-m-d');
        $price = $offer->priceCents / 100;
        $num = $offer->flightNumber;

        $hasChanges = $flight->flight_number !== $num
            || $flight->departure_time !== $depTime
            || $flight->arrival_time !== $arrTime
            || (float) $flight->price_adult !== (float) $price;

        $flight->update([
            'flight_number' => $num,
            'departure_time' => $depTime,
            'arrival_time' => $arrTime,
            'arrival_date' => $arrDate,
            'price_adult' => $price,
        ]);

        return $hasChanges;
    }
}
