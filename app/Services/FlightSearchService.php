<?php

// File: app/Services/FlightSearchService.php

namespace App\Services;

use App\Contracts\FlightProviderInterface;
use App\DTOs\FlightOffer;
use App\Enums\TimeRange;
use App\Models\TourTemplateLeg;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class FlightSearchService
{
    public function __construct(
        private readonly FlightProviderInterface $provider,
    ) {}

    /**
     * Search flights for a template leg.
     * Tries local DB first, falls back to external provider if stale or empty.
     *
     * @return FlightOffer[]
     */
    public function searchForLeg(TourTemplateLeg $leg, string $sortBy = 'price'): array
    {
        $leg->load('departureCity.airports', 'arrivalCity.airports');

        $fromIata = $leg->departureCity->airports->first()?->code;
        $toIata = $leg->arrivalCity->airports->first()?->code;

        if (! $fromIata || ! $toIata) {
            return [];
        }

        $date = $leg->departure_date->format('Y-m-d');

        // 1. Try local DB
        $offers = $this->searchLocalDb($fromIata, $toIata, $date, $leg->preferred_time_range);

        // 2. Fall back to provider if no local results or data is stale
        $staleHours = config('tour.flight_stale_hours', 24);
        if (empty($offers) || $this->isLocalDataStale($fromIata, $toIata, $date, $staleHours)) {
            $providerOffers = $this->searchProvider($fromIata, $toIata, $date, $leg->passenger_count);
            if (! empty($providerOffers)) {
                $offers = array_merge($offers, $providerOffers);
                // Deduplicate by flight number + time
                $offers = $this->deduplicateOffers($offers);
            }
        }

        // 3. Filter by time preference
        if ($leg->preferred_time_range !== TimeRange::Any) {
            $offers = array_filter($offers, function (FlightOffer $o) use ($leg) {
                return $leg->preferred_time_range->matchesHour((int) $o->departureAt->format('H'));
            });
            $offers = array_values($offers);
        }

        // 4. Sort
        usort($offers, match ($sortBy) {
            'time' => fn (FlightOffer $a, FlightOffer $b) => $a->departureAt <=> $b->departureAt,
            'duration' => fn (FlightOffer $a, FlightOffer $b) => $a->durationMinutes() <=> $b->durationMinutes(),
            default => fn (FlightOffer $a, FlightOffer $b) => $a->priceCents <=> $b->priceCents,
        });

        return $offers;
    }

    /**
     * @return FlightOffer[]
     */
    private function searchLocalDb(string $fromIata, string $toIata, string $date, TimeRange $timeRange): array
    {
        $fromAirportIds = DB::table('airports')->where('code', $fromIata)->pluck('id');
        $toAirportIds = DB::table('airports')->where('code', $toIata)->pluck('id');

        if ($fromAirportIds->isEmpty() || $toAirportIds->isEmpty()) {
            return [];
        }

        $flights = DB::table('flights')
            ->join('airlines', 'flights.airline_id', '=', 'airlines.id')
            ->whereIn('flights.from_airport_id', $fromAirportIds)
            ->whereIn('flights.to_airport_id', $toAirportIds)
            ->where('flights.departure_date', $date)
            ->where('flights.is_active', true)
            ->select('flights.*', 'airlines.code as airline_code')
            ->get();

        return $flights->map(function ($f) use ($fromIata, $toIata) {
            $depDate = $f->departure_date;
            $depTime = $f->departure_time ?? '00:00:00';
            $arrDate = $f->arrival_date ?? $f->departure_date;
            $arrTime = $f->arrival_time ?? '23:59:00';

            return new FlightOffer(
                id: "local-{$f->id}",
                airlineCode: $f->airline_code,
                flightNumber: $f->flight_number,
                originIata: $fromIata,
                destinationIata: $toIata,
                departureAt: new DateTimeImmutable("{$depDate} {$depTime}"),
                arrivalAt: new DateTimeImmutable("{$arrDate} {$arrTime}"),
                priceCents: (int) round(((float) $f->price_adult) * 100),
                currency: 'USD',
                seatsAvailable: (int) ($f->available_seats ?? 0),
                source: 'local_db',
                localFlightId: $f->id,
                providerFlightId: null,
                rawData: ['flight_id' => $f->id],
            );
        })->all();
    }

    /**
     * @return FlightOffer[]
     */
    private function searchProvider(string $fromIata, string $toIata, string $date, int $passengers): array
    {
        return $this->provider->search($fromIata, $toIata, $date, $passengers);
    }

    private function isLocalDataStale(string $fromIata, string $toIata, string $date, int $staleHours): bool
    {
        $fromAirportIds = DB::table('airports')->where('code', $fromIata)->pluck('id');
        $toAirportIds = DB::table('airports')->where('code', $toIata)->pluck('id');

        $latestUpdate = DB::table('flights')
            ->whereIn('from_airport_id', $fromAirportIds)
            ->whereIn('to_airport_id', $toAirportIds)
            ->where('departure_date', $date)
            ->max('updated_at');

        if (! $latestUpdate) {
            return true;
        }

        return now()->diffInHours($latestUpdate) >= $staleHours;
    }

    /**
     * @param FlightOffer[] $offers
     * @return FlightOffer[]
     */
    private function deduplicateOffers(array $offers): array
    {
        $seen = [];
        $unique = [];
        foreach ($offers as $offer) {
            $key = $offer->airlineCode . $offer->flightNumber . $offer->departureAt->format('Y-m-d H:i');
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $offer;
            }
        }
        return $unique;
    }
}
