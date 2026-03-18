<?php

namespace App\Services;

use App\Models\Tour;

class TourPricingService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
    ) {}

    /**
     * Calculate and persist the price for a single tour.
     */
    public function recalculate(Tour $tour): ?string
    {
        $tour->loadMissing(['hotel', 'flights', 'tourPrices', 'stays', 'stays.hotel', 'stays.currency']);
        $targetCurrencyId = $tour->currency_id;

        $activeTourPrices = $tour->tourPrices->where('is_active', true);
        if ($activeTourPrices->isNotEmpty()) {
            $lowestAdultPrice = $activeTourPrices->min('price_adult');
            $lowestTourPrice = $activeTourPrices->firstWhere('price_adult', $lowestAdultPrice);
            $hotelPrice = $this->currencyConverter->convert(
                (string) $lowestAdultPrice,
                $lowestTourPrice->currency_id,
                $targetCurrencyId,
            );
        } else {
            $hotelPrice = $this->getHotelPrice($tour, $targetCurrencyId);
        }

        $flightPrice = $this->getFlightPrice($tour, $targetCurrencyId);
        $baseCost = bcadd($hotelPrice ?? '0', $flightPrice, 2);

        if (bccomp($baseCost, '0', 2) <= 0) {
            return null;
        }

        $markupPercent = (string) $tour->getEffectiveMarkupPercent();
        $finalPrice = $this->applyMarkup($baseCost, $markupPercent);

        $tour->updateQuietly(['price' => $finalPrice]);

        return $finalPrice;
    }

    /**
     * Recalculate prices for all tours linked to a specific hotel.
     */
    public function recalculateForHotel(int $hotelId): void
    {
        Tour::where('hotel_id', $hotelId)->each(
            fn (Tour $tour) => $this->recalculate($tour)
        );
    }

    /**
     * Recalculate prices for all tours linked to a specific flight.
     */
    public function recalculateForFlight(int $flightId): void
    {
        Tour::whereHas('flights', fn ($q) => $q->where('flights.id', $flightId))
            ->each(fn (Tour $tour) => $this->recalculate($tour));
    }

    /**
     * Recalculate ALL tour prices (used when global markup changes).
     */
    public function recalculateAll(): int
    {
        $count = 0;
        Tour::each(function (Tour $tour) use (&$count) {
            if ($this->recalculate($tour) !== null) {
                $count++;
            }
        });

        return $count;
    }

    /**
     * Get the price breakdown for display in admin/frontend.
     */
    public function getBreakdown(Tour $tour): array
    {
        $tour->loadMissing(['hotel', 'flights', 'stays', 'stays.hotel', 'stays.currency']);
        $targetCurrencyId = $tour->currency_id;

        $hotelPrice = $this->getHotelPrice($tour, $targetCurrencyId) ?? '0';
        $flightPrice = $this->getFlightPrice($tour, $targetCurrencyId);
        $baseCost = bcadd($hotelPrice, $flightPrice, 2);
        $markupPercent = (string) $tour->getEffectiveMarkupPercent();
        $markupAmount = bcdiv(bcmul($baseCost, $markupPercent, 4), '100', 2);

        return [
            'hotel_price' => $hotelPrice,
            'flight_price' => $flightPrice,
            'base_cost' => $baseCost,
            'markup_percent' => $markupPercent,
            'markup_amount' => $markupAmount,
            'total_price' => bcadd($baseCost, $markupAmount, 2),
        ];
    }

    /**
     * Calculate the booking price based on room type and tourist age categories.
     */
    public function calculateBookingPrice(
        Tour $tour,
        int $roomTypeId,
        int $adults,
        int $children = 0,
        int $infants = 0,
    ): ?array {
        $tour->loadMissing(['tourPrices', 'flights', 'stays', 'stays.hotel', 'stays.currency']);

        $tourPrice = $tour->tourPrices
            ->where('room_type_id', $roomTypeId)
            ->where('is_active', true)
            ->first();

        if (! $tourPrice) {
            return null;
        }

        $targetCurrencyId = $tour->currency_id;

        $hotelAdult = $this->currencyConverter->convert(
            (string) $tourPrice->price_adult, $tourPrice->currency_id, $targetCurrencyId
        );
        $hotelChild = $this->currencyConverter->convert(
            (string) ($tourPrice->price_child ?? 0), $tourPrice->currency_id, $targetCurrencyId
        );
        $hotelInfant = $this->currencyConverter->convert(
            (string) ($tourPrice->price_infant ?? 0), $tourPrice->currency_id, $targetCurrencyId
        );

        $hotelTotal = bcadd(
            bcadd(bcmul($hotelAdult, (string) $adults, 2), bcmul($hotelChild, (string) $children, 2), 2),
            bcmul($hotelInfant, (string) $infants, 2),
            2
        );

        $flightTotal = '0';
        $flightPerAdult = '0';
        foreach ($tour->flights as $flight) {
            $adultPrice = $this->currencyConverter->convert(
                (string) $flight->price_adult, $flight->currency_id, $targetCurrencyId
            );
            $childPrice = $this->currencyConverter->convert(
                (string) ($flight->price_child ?? $flight->price_adult), $flight->currency_id, $targetCurrencyId
            );
            $infantPrice = $this->currencyConverter->convert(
                (string) ($flight->price_infant ?? 0), $flight->currency_id, $targetCurrencyId
            );

            $flightSegment = bcadd(
                bcadd(bcmul($adultPrice, (string) $adults, 2), bcmul($childPrice, (string) $children, 2), 2),
                bcmul($infantPrice, (string) $infants, 2),
                2
            );
            $flightTotal = bcadd($flightTotal, $flightSegment, 2);
            $flightPerAdult = bcadd($flightPerAdult, $adultPrice, 2);
        }

        $baseCost = bcadd($hotelTotal, $flightTotal, 2);
        $markupPercent = (string) $tour->getEffectiveMarkupPercent();
        $markupAmount = bcdiv(bcmul($baseCost, $markupPercent, 4), '100', 2);
        $total = bcadd($baseCost, $markupAmount, 2);

        $perAdultBase = bcadd($hotelAdult, $flightPerAdult, 2);
        $perAdult = $this->applyMarkup($perAdultBase, $markupPercent);

        return [
            'hotel_total' => $hotelTotal,
            'flight_total' => $flightTotal,
            'base_cost' => $baseCost,
            'markup_percent' => $markupPercent,
            'markup_amount' => $markupAmount,
            'total' => $total,
            'per_adult' => $perAdult,
        ];
    }

    /**
     * Apply markup once at the end — no intermediate rounding.
     */
    private function applyMarkup(string $baseCost, string $markupPercent): string
    {
        $markupMultiplier = bcadd('1', bcdiv($markupPercent, '100', 6), 6);

        return bcmul($baseCost, $markupMultiplier, 2);
    }

    private function getHotelPrice(Tour $tour, ?int $targetCurrencyId): ?string
    {
        // Multi-stay: sum price_per_person × nights for each stay
        if ($tour->stays->isNotEmpty()) {
            $total = '0';
            foreach ($tour->stays as $stay) {
                $price = $stay->price_per_person ?? $stay->hotel?->price_per_person;
                if (! $price) {
                    continue;
                }
                $currencyId = $stay->currency_id ?? $stay->hotel?->currency_id;
                $converted = $this->currencyConverter->convert(
                    (string) $price, $currencyId, $targetCurrencyId
                );
                $stayTotal = bcmul($converted, (string) $stay->nights, 2);
                $total = bcadd($total, $stayTotal, 2);
            }

            return bccomp($total, '0', 2) > 0 ? $total : null;
        }

        // Single hotel fallback
        $hotel = $tour->hotel;
        if (! $hotel || ! $hotel->price_per_person) {
            return null;
        }

        return $this->currencyConverter->convert(
            (string) $hotel->price_per_person,
            $hotel->currency_id,
            $targetCurrencyId,
        );
    }

    private function getFlightPrice(Tour $tour, ?int $targetCurrencyId): string
    {
        $total = '0';
        foreach ($tour->flights as $flight) {
            $converted = $this->currencyConverter->convert(
                (string) $flight->price_adult,
                $flight->currency_id,
                $targetCurrencyId,
            );
            $total = bcadd($total, $converted, 2);
        }

        return $total;
    }
}
