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
            'exclude_basic' => 'true',
            'max_stops' => 'nonstop',
            'sort_by' => 'cheapest',
            'infants_on_lap' => '0',
            'airlines' => $airlineCode ?? '',
        ];
        $data = $this->callApi('/api/flights/one-way', $params);

        if (! $data) {
            return [];
        }

        return $this->parseFlights($data['flights'] ?? [], $originIata, $destinationIata, $departureDate);
    }

    /**
     * Round-trip flight search. Returns outbound and return offers separately.
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
        $data = $this->callApi('/api/flights/round-trip', [
            'origin' => $originIata,
            'destination' => $destinationIata,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'adults' => (string) $passengerCount,
            'cabin_class' => 'economy',
            'exclude_basic' => 'true',
            'max_stops' => 'nonstop',
            'sort_by' => 'cheapest',
            'infants_on_lap' => '0',
            'airlines' => $airlineCode ?? '',
        ]);

        if (! $data) {
            return ['outbound' => [], 'return' => []];
        }

        // Round-trip API may return combined itineraries — parse outbound and return legs
        $flights = $data['flights'] ?? [];
        $outbound = [];
        $return = [];

        foreach ($flights as $i => $flight) {
            $legs = $flight['legs'] ?? [];
            $totalPrice = (float) ($flight['price'] ?? 0);
            $currency = $flight['currency'] ?? 'USD';
            // Split price evenly between outbound and return as approximation
            $halfPrice = (int) round($totalPrice * 100 / 2);

            if (isset($legs[0])) {
                $outbound[] = $this->legToOffer(
                    $legs[0], $originIata, $destinationIata, $departureDate,
                    $halfPrice, $currency, "rt-out-{$i}", $flight
                );
            }
            if (isset($legs[1])) {
                $return[] = $this->legToOffer(
                    $legs[1], $destinationIata, $originIata, $returnDate,
                    $halfPrice, $currency, "rt-ret-{$i}", $flight
                );
            }
        }

        return ['outbound' => $outbound, 'return' => $return];
    }

    /**
     * @return FlightOffer[]
     */
    private function parseFlights(array $flights, string $originIata, string $destIata, string $date): array
    {
        $offers = [];

        foreach ($flights as $i => $flight) {
            $leg = $flight['legs'][0] ?? [];
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
                ->timeout(30)
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
