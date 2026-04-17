<?php

namespace App\Services\Flights;

use App\Models\Flight;
use App\Models\FlightPath;
use App\Models\FlightPathLeg;
use App\Models\FlightPathStay;
use App\Models\TourTemplate;
use App\Models\TourTemplateLeg;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        // Build constraint groups:
        //  - each round-trip pair becomes one group (airlines common to both legs)
        //  - each unpaired leg becomes its own group
        $processed = [];
        $groups = [];
        foreach ($legs as $leg) {
            if (isset($processed[$leg->id])) {
                continue;
            }
            if ($leg->round_trip_pair_id && isset($flightsByLeg[$leg->round_trip_pair_id])) {
                $pairId = $leg->round_trip_pair_id;
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

        return Flight::query()
            ->where('is_active', true)
            ->where('departure_date', $date)
            ->whereIn('airline_id', $allowedAirlineIds)
            ->whereHas('fromAirport', fn ($q) => $q->where('city_id', $leg->departure_city_id))
            ->whereHas('toAirport', fn ($q) => $q->where('city_id', $leg->arrival_city_id))
            ->get()
            ->groupBy('airline_id');
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
