<?php

namespace App\Services\Flights;

use App\Models\Currency;
use App\Models\Flight;
use App\Models\FlightPath;
use App\Models\FlightPathLeg;
use App\Models\FlightPathStay;
use App\Models\TourTemplate;
use App\Models\TourTemplateLeg;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generate FlightPath rows from a TourTemplate for a given base departure date.
 *
 * For each leg the generator looks up live Flight rows that (a) fly the
 * template leg's city pair on the required date and (b) belong to one of the
 * airlines attached to that leg. It then builds every valid airline combo.
 *
 * Round-trip constraint: if leg A.round_trip_pair_id points at leg B, the
 * combo must use the same airline for both legs. That matches how block seats
 * are contracted in practice — one carrier for the outbound+return of a pair.
 *
 * Idempotent: skips combos whose flight-id set already exists under this
 * template + date. Never touches or deletes existing FlightPaths.
 */
class FlightPathGenerator
{
    /** Per-request cache: avoid hitting RapidAPI twice for the same (leg, date). */
    private array $apiFetched = [];

    /** Diagnostics surfaced in the job summary: per-leg counts of API attempts and empty responses. */
    private array $apiStats = ['calls' => 0, 'empty' => 0];

    public function __construct(private RapidApiFlightProvider $rapidApi)
    {
    }

    /**
     * Generate FlightPaths for every candidate base date in the window.
     *
     * Candidate base dates = dates on which at least one flight matches the
     * template's first leg (day_offset=0). Other legs are checked inside
     * generate(); a base date with no valid combo ends up reported in
     * $summary['reasons'].
     *
     * @return array{dates:int,created:int,skipped:int,reasons:array<string,string>}
     */
    public function generateForWindow(TourTemplate $template, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $baseDates = $this->candidateBaseDates($template, $from, $to);

        $created = 0;
        $skipped = 0;
        $reasons = [];
        foreach ($baseDates as $baseDate) {
            $r = $this->generate($template, $baseDate);
            $created += $r['created'];
            $skipped += $r['skipped'];
            if (! empty($r['reason']) && $r['created'] === 0 && $r['skipped'] === 0) {
                $reasons[$baseDate->toDateString()] = $r['reason'];
            }
        }

        return [
            'dates' => count($baseDates),
            'created' => $created,
            'skipped' => $skipped,
            'reasons' => $reasons,
            'api' => $this->apiStats,
        ];
    }

    /**
     * @return array<int,CarbonImmutable>
     */
    private function candidateBaseDates(TourTemplate $template, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $firstLeg = $template->legs()->orderBy('leg_order')->with('airlines')->first();
        if (! $firstLeg) {
            return [];
        }

        $airlineIds = $firstLeg->airlines->pluck('id')->all();
        if (empty($airlineIds)) {
            return [];
        }

        // For rapidapi-sourced legs we can't pre-list base dates (the API
        // decides) — fall back to "every date in window" so the generator
        // itself is the one calling the API. For local_db legs we stay fast.
        if ($firstLeg->flight_source === 'rapidapi') {
            $dates = [];
            for ($d = $from; $d <= $to; $d = $d->addDay()) {
                $dates[] = $d;
            }
            return $dates;
        }

        return Flight::query()
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
    }

    /**
     * @return array{created:int,skipped:int,reason?:string}
     */
    public function generate(TourTemplate $template, CarbonImmutable $baseDate): array
    {
        $template->loadMissing(['legs.airlines', 'stays']);
        $legs = $template->legs->sortBy('leg_order')->values();
        if ($legs->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'reason' => 'no legs'];
        }

        // Find candidate flights per leg, grouped by airline_id.
        $flightsByLeg = [];
        foreach ($legs as $leg) {
            $byAirline = $this->findFlightsForLeg($leg, $baseDate);
            if ($byAirline->isEmpty()) {
                return ['created' => 0, 'skipped' => 0, 'reason' => "no flights for leg {$leg->leg_order}"];
            }
            $flightsByLeg[$leg->id] = $byAirline;
        }

        // Build a symmetric pair map first — the admin UI only requires the
        // pair to be set on one leg, so leg A→B may be known without B→A.
        // Reading both directions into one map avoids the bug where the
        // unpaired side of the link got treated as a standalone group
        // before its peer claimed it.
        $pairMap = [];
        foreach ($legs as $leg) {
            if ($leg->round_trip_pair_id) {
                $pairMap[$leg->id] = $leg->round_trip_pair_id;
                $pairMap[$leg->round_trip_pair_id] = $leg->id;
            }
        }

        // Build constraint groups:
        //  - each round-trip pair becomes one group (airlines common to both legs)
        //  - each unpaired leg becomes its own group
        $processed = [];
        $groups = [];
        foreach ($legs as $leg) {
            if (isset($processed[$leg->id])) {
                continue;
            }
            $pairId = $pairMap[$leg->id] ?? null;
            if ($pairId && isset($flightsByLeg[$pairId])) {
                $common = $flightsByLeg[$leg->id]->keys()
                    ->intersect($flightsByLeg[$pairId]->keys())
                    ->values();
                if ($common->isEmpty()) {
                    return ['created' => 0, 'skipped' => 0, 'reason' => "no shared airline for pair {$leg->id}/{$pairId}"];
                }
                $groups[] = ['leg_ids' => [$leg->id, $pairId], 'airline_ids' => $common->all()];
                $processed[$leg->id] = $processed[$pairId] = true;
            } else {
                $groups[] = ['leg_ids' => [$leg->id], 'airline_ids' => $flightsByLeg[$leg->id]->keys()->all()];
                $processed[$leg->id] = true;
            }
        }

        $combos = $this->cartesianGroups($groups);

        $created = 0;
        $skipped = 0;
        $totalLegs = $legs->count();

        foreach ($combos as $legAirlineMap) {
            // Pick the cheapest flight per leg for this airline choice.
            $chosenFlights = [];
            foreach ($legs as $leg) {
                $airlineId = $legAirlineMap[$leg->id];
                $flight = $flightsByLeg[$leg->id][$airlineId]->sortBy('price_adult')->first();
                $chosenFlights[$leg->leg_order] = $flight;
            }

            if ($this->pathExists($template, $baseDate, $chosenFlights)) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($template, $baseDate, $legs, $chosenFlights, $totalLegs) {
                $totalPrice = collect($chosenFlights)->sum(fn (Flight $f) => (float) $f->price_adult);

                $fp = FlightPath::create([
                    'tour_template_id' => $template->id,
                    'route_name' => $template->route_name,
                    'departure_date' => $baseDate->toDateString(),
                    'departure_city_id' => $template->departure_city_id,
                    'total_price' => $totalPrice,
                    'currency_id' => $chosenFlights[array_key_first($chosenFlights)]->currency_id,
                    'nights' => $template->total_nights,
                    'is_available' => true,
                ]);

                foreach ($legs as $leg) {
                    $flight = $chosenFlights[$leg->leg_order];
                    FlightPathLeg::create([
                        'flight_path_id' => $fp->id,
                        'flight_id' => $flight->id,
                        'leg_order' => $leg->leg_order,
                        'direction' => $leg->leg_order > ($totalLegs / 2) ? 'return' : 'outbound',
                    ]);
                }

                foreach ($template->stays as $stay) {
                    FlightPathStay::create([
                        'flight_path_id' => $fp->id,
                        'city_id' => $stay->city_id,
                        'stay_order' => $stay->stay_order,
                        'nights' => $stay->nights,
                    ]);
                }
            });

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** Flights matching leg route + date, keyed by airline_id → Collection<Flight>. */
    private function findFlightsForLeg(TourTemplateLeg $leg, CarbonImmutable $baseDate): Collection
    {
        $date = $baseDate->addDays($leg->day_offset)->toDateString();
        $allowedAirlineIds = $leg->airlines->pluck('id')->all();
        if (empty($allowedAirlineIds)) {
            return collect();
        }

        if ($leg->flight_source === 'rapidapi') {
            $this->fetchFromRapidApi($leg, $date);
        }

        // whereDate() rather than where(): the flights table contains a mix of
        // 'YYYY-MM-DD' and 'YYYY-MM-DD HH:MM:SS' strings in departure_date
        // (earlier seed migrations round-tripped through Eloquent's date cast).
        // A plain text equality misses the datetime-suffix rows.
        return Flight::query()
            ->where('is_active', true)
            ->whereDate('departure_date', $date)
            ->whereIn('airline_id', $allowedAirlineIds)
            ->whereHas('fromAirport', fn ($q) => $q->where('city_id', $leg->departure_city_id))
            ->whereHas('toAirport', fn ($q) => $q->where('city_id', $leg->arrival_city_id))
            ->get()
            ->groupBy('airline_id');
    }

    /**
     * Query RapidAPI for each allowed airline on this leg and upsert results
     * into the flights table. The generator then reads them back through the
     * normal local query, so all downstream pricing/booking/display code
     * continues to treat rapidapi-sourced flights identically to seeded ones.
     *
     * Uses DB::table() instead of Eloquent so that date columns stay stored
     * as plain 'YYYY-MM-DD' strings (the rest of the table is date-only).
     *
     * Gracefully no-ops if the API is unreachable or the quota is exhausted —
     * the caller just sees an empty result for that airline.
     */
    private function fetchFromRapidApi(TourTemplateLeg $leg, string $date): void
    {
        $cacheKey = "{$leg->id}|{$date}";
        if (isset($this->apiFetched[$cacheKey])) {
            return;
        }
        $this->apiFetched[$cacheKey] = true;

        $leg->loadMissing(['departureCity.airports', 'arrivalCity.airports', 'airlines']);
        $depAirport = $leg->departureCity?->airports?->firstWhere('is_active', true);
        $arrAirport = $leg->arrivalCity?->airports?->firstWhere('is_active', true);
        if (! $depAirport || ! $arrAirport) {
            Log::warning('RapidAPI leg fetch skipped — missing airports', [
                'leg_id' => $leg->id,
                'departure_city_id' => $leg->departure_city_id,
                'arrival_city_id' => $leg->arrival_city_id,
            ]);
            return;
        }

        $defaultCurrencyId = Currency::where('code', 'USD')->value('id');

        foreach ($leg->airlines as $airline) {
            $this->apiStats['calls']++;
            $offers = $this->rapidApi->search(
                originIata: $depAirport->code,
                destinationIata: $arrAirport->code,
                departureDate: $date,
                passengerCount: 1,
                airlineCode: $airline->code,
            );
            // Gentle throttle between calls; RapidAPI on our plan frequently
            // times out when hammered. Cheap insurance against a whole job
            // failing silently.
            usleep(500_000);

            if (empty($offers)) {
                $this->apiStats['empty']++;
                Log::info('RapidAPI returned no offers', [
                    'leg_id' => $leg->id,
                    'route' => "{$depAirport->code}→{$arrAirport->code}",
                    'date' => $date,
                    'airline' => $airline->code,
                ]);
                continue;
            }

            foreach ($offers as $offer) {
                $flightNumber = trim((string) $offer->flightNumber);
                if ($flightNumber === '') {
                    continue;
                }

                DB::table('flights')->updateOrInsert(
                    [
                        'airline_id' => $airline->id,
                        'flight_number' => $flightNumber,
                        'departure_date' => $date,
                    ],
                    [
                        'from_airport_id' => $depAirport->id,
                        'to_airport_id' => $arrAirport->id,
                        'origin_city_id' => $depAirport->city_id,
                        'destination_city_id' => $arrAirport->city_id,
                        'departure_time' => $offer->departureAt->format('H:i:s'),
                        'arrival_date' => $offer->arrivalAt->format('Y-m-d'),
                        'arrival_time' => $offer->arrivalAt->format('H:i:s'),
                        'price_adult' => round($offer->priceCents / 100, 2),
                        'currency_id' => $defaultCurrencyId ?? 1,
                        'available_seats' => max((int) $offer->seatsAvailable, 1),
                        'class_type' => 'economy',
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    /**
     * Cartesian product over groups. Each group constrains a set of legs to
     * share a single airline choice — so we pick one airline_id per group and
     * fan it out to every leg_id in that group.
     *
     * @param array<int,array{leg_ids:array<int,int>,airline_ids:array<int,int>}> $groups
     * @return array<int,array<int,int>>  Each combo is legId => airlineId
     */
    private function cartesianGroups(array $groups): array
    {
        $combos = [[]];
        foreach ($groups as $group) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($group['airline_ids'] as $airlineId) {
                    $expanded = $combo;
                    foreach ($group['leg_ids'] as $legId) {
                        $expanded[$legId] = $airlineId;
                    }
                    $next[] = $expanded;
                }
            }
            $combos = $next;
        }
        return $combos;
    }

    /**
     * Detect a FlightPath on this date whose airline+route signature matches.
     *
     * Comparing by flight_id is too strict: the flights table can legitimately
     * carry two rows for the same real-world segment (for example a RapidAPI
     * refresh stored a new row with a prefixed flight_number). Those rows are
     * distinct flight_ids but represent the same combo, so we match on the
     * stable shape instead: (airline_id, from_airport_id, to_airport_id,
     * departure_date) per leg, ordered by leg_order.
     */
    private function pathExists(TourTemplate $template, CarbonImmutable $baseDate, array $legFlights): bool
    {
        $wanted = $this->routeSignature($legFlights);

        $candidates = FlightPath::whereDate('departure_date', $baseDate->toDateString())
            ->with(['legs' => fn ($q) => $q->orderBy('leg_order'), 'legs.flight:id,airline_id,from_airport_id,to_airport_id,departure_date'])
            ->get();

        foreach ($candidates as $fp) {
            $existing = $this->routeSignature(
                $fp->legs->sortBy('leg_order')
                    ->mapWithKeys(fn ($l) => [$l->leg_order => $l->flight])
                    ->all()
            );
            if ($existing === $wanted) {
                return true;
            }
        }
        return false;
    }

    /** Stable hash of a FlightPath's airline+route sequence, independent of flight_id. */
    private function routeSignature(array $legFlights): string
    {
        ksort($legFlights);
        $parts = [];
        foreach ($legFlights as $order => $flight) {
            if (! $flight) {
                continue;
            }
            $date = $flight->departure_date instanceof \Carbon\CarbonInterface
                ? $flight->departure_date->toDateString()
                : (string) $flight->departure_date;
            $parts[] = "$order:{$flight->airline_id}:{$flight->from_airport_id}:{$flight->to_airport_id}:{$date}";
        }
        return implode('|', $parts);
    }
}
