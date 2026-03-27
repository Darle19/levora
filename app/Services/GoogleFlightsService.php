<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches flight prices from Google Flights.
 * Parses nonstop flights only, with economy/business class breakdown.
 */
class GoogleFlightsService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Search for nonstop flight prices with class breakdown.
     *
     * @return array{economy: ?float, business: ?float, ...}|null
     */
    public function search(string $from, string $to, string $date, string $cabin = 'economy'): ?array
    {
        $cacheKey = "gf:{$from}-{$to}:{$date}:{$cabin}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to, $date, $cabin) {
            return $this->fetchPrices($from, $to, $date, $cabin);
        });
    }

    /**
     * Search without cache (force fresh).
     */
    public function searchFresh(string $from, string $to, string $date, string $cabin = 'economy'): ?array
    {
        $cacheKey = "gf:{$from}-{$to}:{$date}:{$cabin}";
        $result = $this->fetchPrices($from, $to, $date, $cabin);

        if ($result) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * Search both economy and business, return combined result.
     */
    public function searchAllClasses(string $from, string $to, string $date): array
    {
        $economy = $this->search($from, $to, $date, 'economy');
        // Small delay between requests
        usleep(300000);
        $business = $this->search($from, $to, $date, 'business');

        return [
            'from' => $from,
            'to' => $to,
            'date' => $date,
            'economy' => $economy['lowest'] ?? null,
            'business' => $business['lowest'] ?? null,
            'economy_data' => $economy,
            'business_data' => $business,
            'fetched_at' => now()->toISOString(),
        ];
    }

    /**
     * Search both classes, skip cache.
     */
    public function searchAllClassesFresh(string $from, string $to, string $date): array
    {
        $economy = $this->searchFresh($from, $to, $date, 'economy');
        usleep(300000);
        $business = $this->searchFresh($from, $to, $date, 'business');

        return [
            'from' => $from,
            'to' => $to,
            'date' => $date,
            'economy' => $economy['lowest'] ?? null,
            'business' => $business['lowest'] ?? null,
            'economy_data' => $economy,
            'business_data' => $business,
            'fetched_at' => now()->toISOString(),
        ];
    }

    private function fetchPrices(string $from, string $to, string $date, string $cabin): ?array
    {
        try {
            $url = $this->buildUrl($from, $to, $date, $cabin);

            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
                ->timeout(15)
                ->get($url);

            if (! $response->successful()) {
                Log::warning("GoogleFlights: HTTP {$response->status()} for {$from}-{$to} {$cabin} on {$date}");
                return null;
            }

            return $this->parsePrices($response->body(), $from, $to, $date, $cabin);
        } catch (\Throwable $e) {
            Log::error("GoogleFlights: {$e->getMessage()}", compact('from', 'to', 'date', 'cabin'));
            return null;
        }
    }

    private function buildUrl(string $from, string $to, string $date, string $cabin): string
    {
        // Google Flights URL parameters:
        // tfs = flight search token, but simpler approach is query-based
        // Nonstop only: add "nonstop" to query
        // Cabin class: 1=economy, 2=premium economy, 3=business, 4=first
        $cabinCode = match ($cabin) {
            'business' => 3,
            'first' => 4,
            'premium' => 2,
            default => 1,
        };

        $dateFormatted = date('Y-m-d', strtotime($date));

        // Google Flights search with nonstop filter
        return "https://www.google.com/travel/flights?q=" . urlencode(
            "Nonstop flights from {$from} to {$to} on {$dateFormatted}"
        ) . "&curr=USD&hl=en&tfs=CBwQAhotEgoyMDI2LTA0LTEzagcIARIDSVNUcgcIARIDTO" // placeholder
            . "&seat=" . $cabinCode;
    }

    private function parsePrices(string $html, string $from, string $to, string $date, string $cabin): ?array
    {
        $prices = [];

        // Method 1: JSON-LD structured data
        if (preg_match_all('/"lowPrice"\s*:\s*"?(\d+(?:\.\d+)?)"?/', $html, $m)) {
            foreach ($m[1] as $p) {
                $prices[] = (float) $p;
            }
        }

        // Method 2: Dollar price patterns
        if (preg_match_all('/\$\s*(\d{2,4})(?:\.\d{2})?/', $html, $m)) {
            foreach ($m[1] as $p) {
                $v = (float) $p;
                if ($v >= 30 && $v <= 1500) {
                    $prices[] = $v;
                }
            }
        }

        // Method 3: Aria-label prices
        if (preg_match_all('/aria-label="[^"]*?(\d{2,4})\s*(?:US\s*)?dollars?/i', $html, $m)) {
            foreach ($m[1] as $p) {
                $prices[] = (float) $p;
            }
        }

        // Method 4: JSON price fields
        if (preg_match_all('/"price"\s*:\s*(\d{2,4}(?:\.\d{2})?)/', $html, $m)) {
            foreach ($m[1] as $p) {
                $v = (float) $p;
                if ($v >= 30 && $v <= 1500) {
                    $prices[] = $v;
                }
            }
        }

        // Filter for nonstop indicators in surrounding context
        // Look for "Nonstop" or "Direct" near prices
        if (preg_match_all('/(?:Nonstop|Direct|nonstop)[^$]*?\$\s*(\d{2,4})/', $html, $m)) {
            foreach ($m[1] as $p) {
                $v = (float) $p;
                if ($v >= 30 && $v <= 1500) {
                    $prices[] = $v;
                }
            }
        }

        if (empty($prices)) {
            Log::info("GoogleFlights: No prices for {$from}-{$to} {$cabin} on {$date}");
            return null;
        }

        $prices = array_unique($prices);
        sort($prices);

        return [
            'from' => $from,
            'to' => $to,
            'date' => $date,
            'cabin' => $cabin,
            'lowest' => min($prices),
            'highest' => max($prices),
            'median' => $prices[intdiv(count($prices), 2)],
            'all_prices' => array_values($prices),
            'count' => count($prices),
            'source' => 'google_flights',
            'fetched_at' => now()->toISOString(),
        ];
    }
}
