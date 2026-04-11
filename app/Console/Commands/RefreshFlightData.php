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

        $flights = Flight::with(['airline', 'fromAirport.city.airports', 'toAirport.city.airports'])
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

        // Group flights by city+date+airline (so IST and SAW for Istanbul are in one group)
        $groupKey = fn (Flight $f) => $f->origin_city_id . '-' . $f->destination_city_id . '-' . $f->departure_date->format('Y-m-d') . '-' . $f->airline_id;
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
     * Searches all airport combinations for the origin and destination cities.
     */
    private function processRoundTripGroup($outboundFlights, $returnFlights, RapidApiFlightProvider $provider): array
    {
        $sampleOut = $outboundFlights->first();
        $sampleRet = $returnFlights->first();
        $depDate = $sampleOut->departure_date->format('Y-m-d');
        $retDate = $sampleRet->departure_date->format('Y-m-d');
        $airlineCode = $sampleOut->airline->code;

        $originAirports = $sampleOut->fromAirport->city->airports->where('is_active', true)->pluck('code')->all();
        $destAirports = $sampleOut->toAirport->city->airports->where('is_active', true)->pluck('code')->all();

        if (empty($originAirports)) $originAirports = [$sampleOut->fromAirport->code];
        if (empty($destAirports)) $destAirports = [$sampleOut->toAirport->code];

        $originCity = $sampleOut->fromAirport->city->name_en ?? implode('/', $originAirports);
        $destCity = $sampleOut->toAirport->city->name_en ?? implode('/', $destAirports);

        $this->line("  RT: {$originCity}↔{$destCity} {$depDate}/{$retDate} [{$airlineCode}]...");

        // Search all origin→dest airport combinations (RT API returns RT total price)
        $allOffers = [];
        foreach ($originAirports as $origCode) {
            foreach ($destAirports as $destCode) {
                $offers = $provider->searchRoundTripOutbound($origCode, $destCode, $depDate, $retDate, 1, $airlineCode);
                foreach ($offers as $o) {
                    $allOffers[] = $o;
                }
                usleep(300_000);
            }
        }

        if (empty($allOffers)) {
            $this->warn("    RT API empty, falling back to one-way.");
            $r1 = $this->processOneWayGroup($outboundFlights, $provider);
            $r2 = $this->processOneWayGroup($returnFlights, $provider);
            return ['updated' => $r1['updated'] + $r2['updated'], 'failed' => $r1['failed'] + $r2['failed']];
        }

        $best = $this->pickCheapestDaytime($allOffers);
        if (! $best) {
            $this->warn("    No daytime flights in RT result.");
            return ['updated' => 0, 'failed' => $outboundFlights->count() + $returnFlights->count()];
        }

        // $best->priceCents is the RT total (full round trip for 1 adult).
        $rtTotalCents = $best->priceCents;
        $halfPrice = round($rtTotalCents / 2 / 100, 2);

        $fromAirport = \App\Models\Airport::where('code', $best->originIata)->first();
        $toAirport = \App\Models\Airport::where('code', $best->destinationIata)->first();

        // Update all outbound flights with best offer
        foreach ($outboundFlights as $f) {
            $f->update([
                'from_airport_id' => $fromAirport?->id ?? $f->from_airport_id,
                'to_airport_id' => $toAirport?->id ?? $f->to_airport_id,
                'flight_number' => $best->flightNumber,
                'departure_time' => $best->departureAt->format('H:i:s'),
                'arrival_time' => $best->arrivalAt->format('H:i:s'),
                'arrival_date' => $best->arrivalAt->format('Y-m-d'),
                'price_adult' => $halfPrice,
            ]);
        }

        // Update all return flights with mirrored airports (return = best reversed)
        foreach ($returnFlights as $f) {
            $f->update([
                'from_airport_id' => $toAirport?->id ?? $f->from_airport_id,
                'to_airport_id' => $fromAirport?->id ?? $f->to_airport_id,
                'price_adult' => $halfPrice,
            ]);
        }

        $count = $outboundFlights->count() + $returnFlights->count();
        $this->info("    ✓ {$airlineCode} {$best->flightNumber} {$best->originIata}→{$best->destinationIata} RT total: \$" . ($rtTotalCents / 100) . " (split \$" . $halfPrice . " per leg, {$count} flight(s) updated)");

        return ['updated' => $count, 'failed' => 0];
    }

    private function processOneWayGroup($groupFlights, RapidApiFlightProvider $provider): array
    {
        $sample = $groupFlights->first();
        $date = $sample->departure_date->format('Y-m-d');
        $airlineCode = $sample->airline->code;

        // Get all active airports for the origin and destination cities
        $originAirports = $sample->fromAirport->city->airports->where('is_active', true)->pluck('code')->all();
        $destAirports = $sample->toAirport->city->airports->where('is_active', true)->pluck('code')->all();

        if (empty($originAirports)) {
            $originAirports = [$sample->fromAirport->code];
        }
        if (empty($destAirports)) {
            $destAirports = [$sample->toAirport->code];
        }

        $originCity = $sample->fromAirport->city->name_en ?? implode('/', $originAirports);
        $destCity = $sample->toAirport->city->name_en ?? implode('/', $destAirports);

        $this->line("  OW: {$originCity} [" . implode(',', $originAirports) . "]→{$destCity} [" . implode(',', $destAirports) . "] {$date} [{$airlineCode}]...");

        // Search all airport combinations
        $allOffers = [];
        foreach ($originAirports as $origCode) {
            foreach ($destAirports as $destCode) {
                $offers = $provider->search($origCode, $destCode, $date, 1, $airlineCode);
                foreach ($offers as $o) {
                    $allOffers[] = $o;
                }
                usleep(300_000);
            }
        }

        if (empty($allOffers)) {
            $this->warn("    No results from API.");
            return ['updated' => 0, 'failed' => $groupFlights->count()];
        }

        $best = $this->pickCheapestDaytime($allOffers);
        if (! $best) {
            $this->warn("    No daytime flights (" . self::DEP_TIME_MIN . "–" . self::DEP_TIME_MAX . ").");
            return ['updated' => 0, 'failed' => $groupFlights->count()];
        }

        // Find airport models by code for updating from_airport_id / to_airport_id
        $fromAirport = \App\Models\Airport::where('code', $best->originIata)->first();
        $toAirport = \App\Models\Airport::where('code', $best->destinationIata)->first();

        $this->info("    ✓ {$airlineCode} {$best->flightNumber} {$best->originIata}→{$best->destinationIata} {$best->departureAt->format('H:i')} \$" . ($best->priceCents / 100));

        $updated = 0;
        foreach ($groupFlights as $flight) {
            $flight->update([
                'from_airport_id' => $fromAirport?->id ?? $flight->from_airport_id,
                'to_airport_id' => $toAirport?->id ?? $flight->to_airport_id,
                'flight_number' => $best->flightNumber,
                'departure_time' => $best->departureAt->format('H:i:s'),
                'arrival_time' => $best->arrivalAt->format('H:i:s'),
                'arrival_date' => $best->arrivalAt->format('Y-m-d'),
                'price_adult' => $best->priceCents / 100,
            ]);
            $updated++;
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
