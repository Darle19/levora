<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches nonstop flight prices via RapidAPI Google Flights Scraper.
 * Used for IST↔NCE and IST↔GYD daily price updates.
 */
class RapidApiFlightService
{
    private const BASE_URL = 'https://google-flights-scraper.p.rapidapi.com/api/flights/one-way';
    private const CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private readonly string $apiKey,
    ) {}

    /**
     * Search for cheapest nonstop economy flight on a given date.
     *
     * @return array{price: float, airline: string, flight_number: string, dep_time: string, arr_time: string}|null
     */
    public function searchCheapest(string $from, string $to, string $date): ?array
    {
        $cacheKey = "rapidapi_flight:{$from}-{$to}:{$date}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to, $date) {
            return $this->fetch($from, $to, $date);
        });
    }

    /**
     * Search without cache.
     */
    public function searchFresh(string $from, string $to, string $date): ?array
    {
        $cacheKey = "rapidapi_flight:{$from}-{$to}:{$date}";
        $result = $this->fetch($from, $to, $date);

        if ($result) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    private function fetch(string $from, string $to, string $date): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('RapidApiFlightService: RAPIDAPI_KEY not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'google-flights-scraper.p.rapidapi.com',
                'x-rapidapi-key' => $this->apiKey,
            ])
                ->timeout(30)
                ->get(self::BASE_URL, [
                    'origin' => $from,
                    'destination' => $to,
                    'departure_date' => $date,
                    'adults' => '1',
                    'cabin_class' => 'economy',
                    'max_stops' => 'nonstop',
                    'sort_by' => 'cheapest',
                    'infants_on_lap' => '0',
                    'airlines' => '', // all airlines
                ]);

            if (! $response->successful()) {
                Log::warning("RapidAPI: HTTP {$response->status()} for {$from}→{$to} {$date}", [
                    'body' => substr($response->body(), 0, 200),
                ]);
                return null;
            }

            $data = $response->json();
            $flights = $data['flights'] ?? [];

            if (empty($flights)) {
                Log::info("RapidAPI: No nonstop flights for {$from}→{$to} on {$date}");
                return null;
            }

            // Take cheapest
            $cheapest = $flights[0];
            $leg = $cheapest['legs'][0] ?? [];

            return [
                'price' => (float) $cheapest['price'],
                'currency' => $cheapest['currency'] ?? 'USD',
                'airline' => $cheapest['airline_name'] ?? 'Unknown',
                'airline_code' => $leg['airline'] ?? '',
                'flight_number' => $leg['flight_number'] ?? '',
                'departure_time' => substr($leg['departure_time'] ?? '', 11, 5),
                'arrival_time' => substr($leg['arrival_time'] ?? '', 11, 5),
                'duration_minutes' => $cheapest['total_duration_minutes'] ?? null,
                'from' => $from,
                'to' => $to,
                'date' => $date,
                'fetched_at' => now()->toISOString(),
            ];
        } catch (\Throwable $e) {
            Log::error("RapidAPI: {$e->getMessage()}", compact('from', 'to', 'date'));
            return null;
        }
    }
}
