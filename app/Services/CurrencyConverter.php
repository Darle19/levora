<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Log;

class CurrencyConverter
{
    /** @var array<string, float|null> In-memory rate cache to avoid repeated DB queries within a request */
    private static array $ratesCache = [];

    public function convert(float $amount, ?int $fromCurrencyId, ?int $toCurrencyId): float
    {
        if (! $fromCurrencyId || ! $toCurrencyId || $fromCurrencyId === $toCurrencyId) {
            return $amount;
        }

        $cacheKey = "{$fromCurrencyId}-{$toCurrencyId}";

        if (! array_key_exists($cacheKey, self::$ratesCache)) {
            self::$ratesCache[$cacheKey] = $this->resolveRate($fromCurrencyId, $toCurrencyId);
        }

        $rate = self::$ratesCache[$cacheKey];

        if ($rate === null) {
            Log::warning("No currency rate found: {$fromCurrencyId} -> {$toCurrencyId}");
            return $amount;
        }

        return round($amount * $rate, 2);
    }

    private function resolveRate(int $fromCurrencyId, int $toCurrencyId): ?float
    {
        // Try direct rate
        $rate = CurrencyRate::where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $toCurrencyId)
            ->orderByDesc('date')
            ->value('rate');

        if ($rate) {
            return (float) $rate;
        }

        // Try inverse rate
        $inverseRate = CurrencyRate::where('from_currency_id', $toCurrencyId)
            ->where('to_currency_id', $fromCurrencyId)
            ->orderByDesc('date')
            ->value('rate');

        if ($inverseRate && $inverseRate > 0) {
            return 1 / (float) $inverseRate;
        }

        return null;
    }

    /** Clear the in-memory cache (useful in tests) */
    public static function clearCache(): void
    {
        self::$ratesCache = [];
    }
}
