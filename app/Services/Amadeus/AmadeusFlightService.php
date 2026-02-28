<?php

namespace App\Services\Amadeus;

use App\DTOs\FlightOfferDto;
use App\Models\Flight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmadeusFlightService
{
    /**
     * Only show flights from these airlines.
     */
    private const ALLOWED_AIRLINES = ['HY', 'TK', 'C2', 'ID'];

    public function __construct(
        private AmadeusAuthService $authService,
    ) {}

    /**
     * Search flight offers via Amadeus API with cache + local fallback.
     *
     * @return FlightOfferDto[]
     */
    public function searchFlights(
        string $origin,
        string $destination,
        string $departureDate,
        ?string $returnDate = null,
        int $adults = 1,
        int $children = 0,
        int $infants = 0,
        string $travelClass = 'ECONOMY',
        bool $nonStop = false,
        int $maxResults = 20,
    ): array {
        $params = compact('origin', 'destination', 'departureDate', 'returnDate', 'adults', 'children', 'infants', 'travelClass', 'nonStop');
        $cacheKey = hash('sha256', json_encode($params));

        // Check cache
        $cached = $this->getCachedResults($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Try Amadeus API
        $results = $this->searchAmadeus($params, $maxResults);

        if ($results !== null) {
            $this->cacheResults($cacheKey, $results);
            return $results;
        }

        // Fallback to local flights
        return $this->searchLocalFlights($origin, $destination, $departureDate, $returnDate);
    }

    private function searchAmadeus(array $params, int $maxResults): ?array
    {
        $token = $this->authService->getAccessToken();
        if (! $token) {
            return null;
        }

        $baseUrl = config('amadeus.base_url');

        $queryParams = [
            'originLocationCode' => $params['origin'],
            'destinationLocationCode' => $params['destination'],
            'departureDate' => $params['departureDate'],
            'adults' => $params['adults'],
            'max' => $maxResults,
            'currencyCode' => 'USD',
        ];

        if ($params['returnDate']) {
            $queryParams['returnDate'] = $params['returnDate'];
        }
        if ($params['children'] > 0) {
            $queryParams['children'] = $params['children'];
        }
        if ($params['infants'] > 0) {
            $queryParams['infants'] = $params['infants'];
        }
        if ($params['travelClass'] !== 'ECONOMY') {
            $queryParams['travelClass'] = $params['travelClass'];
        }
        if ($params['nonStop']) {
            $queryParams['nonStop'] = 'true';
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->get("{$baseUrl}/v2/shopping/flight-offers", $queryParams);

            if (! $response->successful()) {
                Log::warning('Amadeus flight search failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return null;
            }

            $data = $response->json();
            $dictionaries = $data['dictionaries'] ?? [];
            $offers = $data['data'] ?? [];

            $results = array_map(
                fn(array $offer) => FlightOfferDto::fromAmadeus($offer, $dictionaries),
                $offers
            );

            // Filter to allowed airlines only
            return array_values(array_filter(
                $results,
                fn(FlightOfferDto $dto) => in_array($dto->airline, self::ALLOWED_AIRLINES, true)
            ));
        } catch (\Exception $e) {
            Log::error('Amadeus flight search exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return FlightOfferDto[]
     */
    private function searchLocalFlights(string $origin, string $destination, string $departureDate, ?string $returnDate): array
    {
        $flights = Flight::where('is_active', true)
            ->whereHas('fromAirport', fn($q) => $q->where('code', $origin))
            ->whereHas('toAirport', fn($q) => $q->where('code', $destination))
            ->whereHas('airline', fn($q) => $q->whereIn('code', self::ALLOWED_AIRLINES))
            ->where('departure_date', '>=', $departureDate)
            ->with(['airline', 'fromAirport', 'toAirport', 'currency'])
            ->orderBy('price_adult')
            ->limit(20)
            ->get();

        $results = $flights->map(fn(Flight $f) => FlightOfferDto::fromLocalFlight($f))->all();

        // If return date specified, find return flights too
        if ($returnDate) {
            $returnFlights = Flight::where('is_active', true)
                ->whereHas('fromAirport', fn($q) => $q->where('code', $destination))
                ->whereHas('toAirport', fn($q) => $q->where('code', $origin))
                ->whereHas('airline', fn($q) => $q->whereIn('code', self::ALLOWED_AIRLINES))
                ->where('departure_date', '>=', $returnDate)
                ->with(['airline', 'fromAirport', 'toAirport', 'currency'])
                ->orderBy('price_adult')
                ->limit(20)
                ->get();

            // Merge return flight info - for simplicity, return outbound flights
            // In a real app, you'd pair them up
        }

        return $results;
    }

    /**
     * @return FlightOfferDto[]|null
     */
    private function getCachedResults(string $cacheKey): ?array
    {
        $cached = DB::table('amadeus_flight_cache')
            ->where('search_hash', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        if (! $cached) {
            return null;
        }

        $data = json_decode($cached->response_data, true);

        return array_map(fn(array $item) => new FlightOfferDto(...$item), $data);
    }

    private function cacheResults(string $cacheKey, array $results): void
    {
        $ttl = (int) config('amadeus.cache_ttl', 30);
        $data = array_map(fn(FlightOfferDto $dto) => $dto->toArray(), $results);

        DB::table('amadeus_flight_cache')->updateOrInsert(
            ['search_hash' => $cacheKey],
            [
                'response_data' => json_encode($data),
                'expires_at' => now()->addMinutes($ttl),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
