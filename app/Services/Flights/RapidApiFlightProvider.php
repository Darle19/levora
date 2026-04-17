<?php

namespace App\Services\Flights;

use App\Contracts\FlightProviderInterface;
use App\DTOs\FlightOffer;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Flight provider using RapidAPI Google Flights Scraper.
 * Supports one-way and round-trip searches.
 */
class RapidApiFlightProvider implements FlightProviderInterface
{
    private const HOST = 'google-flights-scraper.p.rapidapi.com';

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.rapidapi.key', '');
    }

    public function name(): string
    {
        return 'rapidapi';
    }

    /**
     * One-way flight search.
     *
     * @return FlightOffer[]
     */
    public function search(
        string $originIata,
        string $destinationIata,
        string $departureDate,
        int $passengerCount = 1,
        ?string $airlineCode = null,
    ): array {
        $params = [
            'origin' => $originIata,
            'destination' => $destinationIata,
            'departure_date' => $departureDate,
            'adults' => (string) $passengerCount,
            'cabin_class' => 'economy',
            'max_stops' => 'nonstop',
            'sort_by' => 'cheapest',
            'infants_on_lap' => '0',
            'airlines' => $airlineCode ?? '',
        ];
        $data = $this->callApi('/api/flights/one-way', $params);

        if (! $data) {
            return [];
        }

        return $this->parseFlights($data['flights'] ?? [], $originIata, $destinationIata, $departureDate, $airlineCode);
    }

    /**
     * Round-trip flight search. Returns outbound offers; each offer's priceCents is the FULL round-trip total.
     * The API returns outbound options with RT total price; return_legs is typically empty.
     * To get return offers, call again with origin/destination swapped.
     *
     * @return FlightOffer[] Outbound offers with RT total price
     */
    public function searchRoundTripOutbound(
        string $originIata,
        string $destinationIata,
        string $departureDate,
        string $returnDate,
        int $passengerCount = 1,
        ?string $airlineCode = null,
    ): array {
        $data = $this->callApi('/api/flights/round-trip', [
            'origin' => $originIata,
            'destination' => $destinationIata,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'adults' => (string) $passengerCount,
            'cabin_class' => 'economy',
            'max_stops' => 'nonstop',
            'sort_by' => 'cheapest',
            'infants_on_lap' => '0',
            'airlines' => $airlineCode ?? '',
        ]);

        if (! $data) {
            return [];
        }

        $offers = [];
        foreach ($data['flights'] ?? [] as $i => $flight) {
            // Prefer outbound_legs over legs (more reliable in RT response)
            $legs = $flight['outbound_legs'] ?? $flight['legs'] ?? [];
            $leg = $legs[0] ?? null;
            if (! $leg) {
                continue;
            }

            // Strict airline filter — API returns other airlines even when filtered
            if ($airlineCode && isset($leg['airline']) && strtoupper($leg['airline']) !== strtoupper($airlineCode)) {
                continue;
            }

            $totalPrice = (float) ($flight['price'] ?? 0);
            $currency = $flight['currency'] ?? 'USD';

            $offers[] = $this->legToOffer(
                $leg, $originIata, $destinationIata, $departureDate,
                (int) round($totalPrice * 100), $currency, "rt-{$i}", $flight
            );
        }

        return $offers;
    }

    /**
     * Legacy wrapper — kept for backwards compatibility.
     *
     * @return array{outbound: FlightOffer[], return: FlightOffer[]}
     */
    public function searchRoundTrip(
        string $originIata,
        string $destinationIata,
        string $departureDate,
        string $returnDate,
        int $passengerCount = 1,
        ?string $airlineCode = null,
    ): array {
        $outbound = $this->searchRoundTripOutbound($originIata, $destinationIata, $departureDate, $returnDate, $passengerCount, $airlineCode);
        $return = $this->searchRoundTripOutbound($destinationIata, $originIata, $returnDate, $departureDate, $passengerCount, $airlineCode);
        return ['outbound' => $outbound, 'return' => $return];
    }

    /**
     * @return FlightOffer[]
     */
    /**
     * @return FlightOffer[]
     */
    private function parseFlights(array $flights, string $originIata, string $destIata, string $date, ?string $airlineCode = null): array
    {
        $offers = [];

        foreach ($flights as $i => $flight) {
            $leg = $flight['legs'][0] ?? [];

            // Strict airline filter — API returns other airlines even when filtered
            if ($airlineCode && isset($leg['airline']) && strtoupper($leg['airline']) !== strtoupper($airlineCode)) {
                continue;
            }

            $price = (float) ($flight['price'] ?? 0);
            $currency = $flight['currency'] ?? 'USD';

            $offers[] = $this->legToOffer(
                $leg, $originIata, $destIata, $date,
                (int) round($price * 100), $currency, "ow-{$i}", $flight
            );
        }

        return $offers;
    }

    private function legToOffer(
        array $leg,
        string $originIata,
        string $destIata,
        string $date,
        int $priceCents,
        string $currency,
        string $idSuffix,
        array $rawFlight,
    ): FlightOffer {
        $depTime = $leg['departure_time'] ?? "{$date}T00:00:00";
        $arrTime = $leg['arrival_time'] ?? "{$date}T23:59:00";
        $airlineCode = $leg['airline'] ?? '';
        $flightNumber = $leg['flight_number'] ?? '';

        return new FlightOffer(
            id: "rapidapi-{$originIata}-{$destIata}-{$date}-{$idSuffix}",
            airlineCode: $airlineCode,
            flightNumber: $flightNumber,
            originIata: $originIata,
            destinationIata: $destIata,
            departureAt: new DateTimeImmutable($depTime),
            arrivalAt: new DateTimeImmutable($arrTime),
            priceCents: $priceCents,
            currency: $currency,
            seatsAvailable: 9, // RapidAPI doesn't return seat count
            source: $this->name(),
            localFlightId: null,
            providerFlightId: "rapidapi-{$idSuffix}",
            rawData: $rawFlight,
        );
    }

    private function callApi(string $endpoint, array $params): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('RapidApiFlightProvider: RAPIDAPI_KEY not configured');
            return null;
        }

        try {
            $url = "https://" . self::HOST . $endpoint;

            $response = Http::withHeaders([
                'x-rapidapi-host' => self::HOST,
                'x-rapidapi-key' => $this->apiKey,
            ])
                ->timeout(60)
                ->retry(2, 2000, throw: false)
                ->get($url, $params);

            if (! $response->successful()) {
                Log::warning("RapidAPI: HTTP {$response->status()} for {$endpoint}", [
                    'params' => $params,
                    'body' => substr($response->body(), 0, 300),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("RapidAPI: {$e->getMessage()}", ['endpoint' => $endpoint, 'params' => $params]);
            return null;
        }
    }
}
