<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightPath;
use App\Services\Flights\RapidApiFlightProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RefreshFlightData extends Command
{
    protected $signature = 'flights:refresh {--days=30 : Refresh flights within N days from now}';
    protected $description = 'Refresh flight times and prices from RapidAPI for upcoming flights';

    // Block-seat carriers with fixed contract prices (Centrum, Qanot Sharq).
    // Cron must not touch their price_adult or flight_number — the seeded
    // value IS the source of truth.
    private const SKIP_AIRLINES = ['C2', 'HH'];
    private const DEP_TIME_MIN = '04:00';
    private const DEP_TIME_MAX = '17:00';

    /** Cache airport models by IATA code to avoid repeated queries. */
    private array $airportCache = [];

    public function handle(RapidApiFlightProvider $provider): int
    {
        $days = (int) $this->option('days');
        $from = now();
        $to = now()->addDays($days);

        // Start-of-run log so we can see in laravel.log whether the command even launched
        // (previously only the completion line was logged, so silent mid-run crashes were invisible).
        Log::info('flights:refresh started', ['days' => $days, 'window' => [$from->toDateTimeString(), $to->toDateTimeString()]]);

        $flights = Flight::with(['airline', 'fromAirport.city.airports', 'toAirport.city.airports'])
            ->where('is_active', true)
            ->whereBetween('departure_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereHas('airline', fn ($q) => $q->whereNotIn('code', self::SKIP_AIRLINES))
            ->orderBy('departure_date')
            ->get()
            ->keyBy('id');

        if ($flights->isEmpty()) {
            $this->info('No eligible flights to refresh.');
            return self::SUCCESS;
        }

        $rtPairs = $this->detectRoundTripPairs($flights->pluck('id')->all());

        $updated = 0;
        $failed = 0;

        $groupKey = fn (Flight $f) => $f->origin_city_id . '-' . $f->destination_city_id . '-' . $f->departure_date->format('Y-m-d') . '-' . $f->airline_id;
        $allGroups = $flights->groupBy($groupKey);

        // Map RT group key pairs
        $rtGroupKeys = [];
        $processedRtGroups = [];

        foreach ($rtPairs as $outboundId => $returnId) {
            $outbound = $flights->get($outboundId);
            $return = $flights->get($returnId);
            if (! $outbound || ! $return) {
                continue;
            }
            $rtGroupKeys[$groupKey($outbound)] = $groupKey($return);
        }

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

        $owGroups = $allGroups->reject(fn ($_, $key) => in_array($key, $processedRtGroups));
        $owFlightCount = $owGroups->flatten()->count();

        $this->info("Refreshing " . ($flights->count() - $owFlightCount) . " flights in " . (count($processedRtGroups) / 2) . " RT pair(s) + {$owFlightCount} flights in " . $owGroups->count() . " one-way call(s)...");

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

    private function detectRoundTripPairs(array $flightIds): array
    {
        $pairs = [];

        $flightPaths = FlightPath::whereHas('legs', fn ($q) => $q->whereIn('flight_id', $flightIds))
            ->with(['legs.flight' => fn ($q) => $q->with('airline')])
            ->get();

        foreach ($flightPaths as $fp) {
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

                if ($a->from_airport_id === $b->to_airport_id
                    && $a->to_airport_id === $b->from_airport_id) {
                    $pairs[$a->id] = $b->id;
                }
            }
        }

        return $pairs;
    }

    private function processRoundTripGroup(Collection $outboundFlights, Collection $returnFlights, RapidApiFlightProvider $provider): array
    {
        $sampleOut = $outboundFlights->first();
        $sampleRet = $returnFlights->first();
        $depDate = $sampleOut->departure_date->format('Y-m-d');
        $retDate = $sampleRet->departure_date->format('Y-m-d');
        $airlineCode = $sampleOut->airline->code;

        [$originAirports, $originCity] = $this->getCityAirportCodes($sampleOut->fromAirport);
        [$destAirports, $destCity] = $this->getCityAirportCodes($sampleOut->toAirport);

        $this->line("  RT: {$originCity}↔{$destCity} {$depDate}/{$retDate} [{$airlineCode}]...");

        $allOffers = $this->searchAirportCombinations(
            $originAirports, $destAirports,
            fn ($orig, $dest) => $provider->searchRoundTripOutbound($orig, $dest, $depDate, $retDate, 1, $airlineCode)
        );

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

        $rtTotalCents = $best->priceCents;
        $halfPrice = round($rtTotalCents / 2 / 100, 2);
        $fromAirport = $this->resolveAirport($best->originIata);
        $toAirport = $this->resolveAirport($best->destinationIata);

        // Do not overwrite a real price with 0 — RapidAPI sometimes returns
        // an empty price payload and we'd zero out the seeded value.
        $priceUpdate = $halfPrice > 0 ? ['price_adult' => $halfPrice] : [];

        foreach ($outboundFlights as $f) {
            $f->update(array_merge([
                'from_airport_id' => $fromAirport?->id ?? $f->from_airport_id,
                'to_airport_id' => $toAirport?->id ?? $f->to_airport_id,
                'flight_number' => $best->flightNumber,
                'departure_time' => $best->departureAt->format('H:i:s'),
                'arrival_time' => $best->arrivalAt->format('H:i:s'),
                'arrival_date' => $best->arrivalAt->format('Y-m-d'),
            ], $priceUpdate));
        }

        foreach ($returnFlights as $f) {
            $f->update(array_merge([
                'from_airport_id' => $toAirport?->id ?? $f->from_airport_id,
                'to_airport_id' => $fromAirport?->id ?? $f->to_airport_id,
            ], $priceUpdate));
        }

        $count = $outboundFlights->count() + $returnFlights->count();
        $this->info("    ✓ {$airlineCode} {$best->flightNumber} {$best->originIata}→{$best->destinationIata} RT total: \$" . ($rtTotalCents / 100) . " (split \$" . $halfPrice . " per leg, {$count} flight(s) updated)");

        return ['updated' => $count, 'failed' => 0];
    }

    private function processOneWayGroup(Collection $groupFlights, RapidApiFlightProvider $provider): array
    {
        $sample = $groupFlights->first();
        $date = $sample->departure_date->format('Y-m-d');
        $airlineCode = $sample->airline->code;

        [$originAirports, $originCity] = $this->getCityAirportCodes($sample->fromAirport);
        [$destAirports, $destCity] = $this->getCityAirportCodes($sample->toAirport);

        $this->line("  OW: {$originCity} [" . implode(',', $originAirports) . "]→{$destCity} [" . implode(',', $destAirports) . "] {$date} [{$airlineCode}]...");

        $allOffers = $this->searchAirportCombinations(
            $originAirports, $destAirports,
            fn ($orig, $dest) => $provider->search($orig, $dest, $date, 1, $airlineCode)
        );

        if (empty($allOffers)) {
            $this->warn("    No results from API.");
            return ['updated' => 0, 'failed' => $groupFlights->count()];
        }

        $best = $this->pickCheapestDaytime($allOffers);
        if (! $best) {
            $this->warn("    No daytime flights (" . self::DEP_TIME_MIN . "–" . self::DEP_TIME_MAX . ").");
            return ['updated' => 0, 'failed' => $groupFlights->count()];
        }

        $fromAirport = $this->resolveAirport($best->originIata);
        $toAirport = $this->resolveAirport($best->destinationIata);

        $newPrice = $best->priceCents / 100;
        $this->info("    ✓ {$airlineCode} {$best->flightNumber} {$best->originIata}→{$best->destinationIata} {$best->departureAt->format('H:i')} \$" . $newPrice);

        // Do not overwrite a real price with 0 — RapidAPI occasionally drops
        // the price field and we must not zero out the seeded value.
        $priceUpdate = $newPrice > 0 ? ['price_adult' => $newPrice] : [];

        $updated = 0;
        foreach ($groupFlights as $flight) {
            $flight->update(array_merge([
                'from_airport_id' => $fromAirport?->id ?? $flight->from_airport_id,
                'to_airport_id' => $toAirport?->id ?? $flight->to_airport_id,
                'flight_number' => $best->flightNumber,
                'departure_time' => $best->departureAt->format('H:i:s'),
                'arrival_time' => $best->arrivalAt->format('H:i:s'),
                'arrival_date' => $best->arrivalAt->format('Y-m-d'),
            ], $priceUpdate));
            $updated++;
        }

        return ['updated' => $updated, 'failed' => 0];
    }

    // ── Shared helpers ──

    /**
     * Get all active airport IATA codes for a given airport's city.
     * @return array{0: string[], 1: string} [codes, cityName]
     */
    private function getCityAirportCodes(Airport $airport): array
    {
        $codes = $airport->city->airports->where('is_active', true)->pluck('code')->all();
        if (empty($codes)) {
            $codes = [$airport->code];
        }
        $cityName = $airport->city->name_en ?? implode('/', $codes);
        return [$codes, $cityName];
    }

    /**
     * Search all origin×dest airport combinations, collecting offers.
     */
    private function searchAirportCombinations(array $originCodes, array $destCodes, callable $searchFn): array
    {
        $allOffers = [];
        foreach ($originCodes as $origCode) {
            foreach ($destCodes as $destCode) {
                foreach ($searchFn($origCode, $destCode) as $offer) {
                    $allOffers[] = $offer;
                }
                usleep(300_000);
            }
        }
        return $allOffers;
    }

    /** Resolve airport model by IATA code (cached). */
    private function resolveAirport(string $code): ?Airport
    {
        return $this->airportCache[$code] ??= Airport::where('code', $code)->first();
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
}
