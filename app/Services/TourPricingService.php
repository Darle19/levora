<?php

namespace App\Services;

use App\Models\Tour;

class TourPricingService
{
    public function __construct(
        private CurrencyConverter $currencyConverter,
    ) {}

    /**
     * Calculate and persist the price for a single tour.
     * Returns the computed price or null if insufficient data.
     */
    public function recalculate(Tour $tour): ?float
    {
        $tour->loadMissing(['hotel', 'flights', 'tourPrices']);
        $targetCurrencyId = $tour->currency_id;

        // Use lowest tour_price (per room type) if available, else fall back to hotel.price_per_person
        $activeTourPrices = $tour->tourPrices->where('is_active', true);
        if ($activeTourPrices->isNotEmpty()) {
            $lowestAdultPrice = $activeTourPrices->min('price_adult');
            $lowestTourPrice = $activeTourPrices->firstWhere('price_adult', $lowestAdultPrice);
            $hotelPrice = $this->currencyConverter->convert(
                (float) $lowestAdultPrice,
                $lowestTourPrice->currency_id,
                $targetCurrencyId,
            );
        } else {
            $hotelPrice = $this->getHotelPrice($tour, $targetCurrencyId);
        }

        $flightPrice = $this->getFlightPrice($tour, $targetCurrencyId);

        $baseCost = ($hotelPrice ?? 0) + $flightPrice;

        if ($baseCost <= 0) {
            return null;
        }

        $markupPercent = $tour->getEffectiveMarkupPercent();
        $finalPrice = round($baseCost * (1 + $markupPercent / 100), 2);

        $tour->updateQuietly(['price' => $finalPrice]);

        return $finalPrice;
    }

    /**
     * Recalculate prices for all tours linked to a specific hotel.
     */
    public function recalculateForHotel(int $hotelId): void
    {
        Tour::where('hotel_id', $hotelId)->each(
            fn(Tour $tour) => $this->recalculate($tour)
        );
    }

    /**
     * Recalculate prices for all tours linked to a specific flight.
     */
    public function recalculateForFlight(int $flightId): void
    {
        Tour::whereHas('flights', fn($q) => $q->where('flights.id', $flightId))
            ->each(fn(Tour $tour) => $this->recalculate($tour));
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
        $tour->loadMissing(['hotel', 'flights']);
        $targetCurrencyId = $tour->currency_id;

        $hotelPrice = $this->getHotelPrice($tour, $targetCurrencyId) ?? 0;
        $flightPrice = $this->getFlightPrice($tour, $targetCurrencyId);
        $baseCost = $hotelPrice + $flightPrice;
        $markupPercent = $tour->getEffectiveMarkupPercent();
        $markupAmount = round($baseCost * $markupPercent / 100, 2);

        return [
            'hotel_price' => $hotelPrice,
            'flight_price' => $flightPrice,
            'base_cost' => $baseCost,
            'markup_percent' => $markupPercent,
            'markup_amount' => $markupAmount,
            'total_price' => round($baseCost + $markupAmount, 2),
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
        int $infants = 0
    ): ?array {
        $tour->loadMissing(['tourPrices', 'flights']);

        $tourPrice = $tour->tourPrices
            ->where('room_type_id', $roomTypeId)
            ->where('is_active', true)
            ->first();

        if (!$tourPrice) {
            return null;
        }

        $targetCurrencyId = $tour->currency_id;

        // Hotel portion from tour_prices (admin-set per-person total for the tour)
        $hotelAdult = $this->currencyConverter->convert(
            (float) $tourPrice->price_adult, $tourPrice->currency_id, $targetCurrencyId
        );
        $hotelChild = $this->currencyConverter->convert(
            (float) ($tourPrice->price_child ?? 0), $tourPrice->currency_id, $targetCurrencyId
        );
        $hotelInfant = $this->currencyConverter->convert(
            (float) ($tourPrice->price_infant ?? 0), $tourPrice->currency_id, $targetCurrencyId
        );

        $hotelTotal = ($hotelAdult * $adults)
            + ($hotelChild * $children)
            + ($hotelInfant * $infants);

        // Flight portion (sum all linked flights × pax by age)
        $flightTotal = 0;
        $flightPerAdult = 0;
        foreach ($tour->flights as $flight) {
            $adultPrice = $this->currencyConverter->convert(
                (float) $flight->price_adult, $flight->currency_id, $targetCurrencyId
            );
            $childPrice = $this->currencyConverter->convert(
                (float) ($flight->price_child ?? $flight->price_adult), $flight->currency_id, $targetCurrencyId
            );
            $infantPrice = $this->currencyConverter->convert(
                (float) ($flight->price_infant ?? 0), $flight->currency_id, $targetCurrencyId
            );

            $flightTotal += ($adultPrice * $adults)
                + ($childPrice * $children)
                + ($infantPrice * $infants);
            $flightPerAdult += $adultPrice;
        }

        $baseCost = $hotelTotal + $flightTotal;
        $markup = $tour->getEffectiveMarkupPercent();
        $markupAmount = round($baseCost * $markup / 100, 2);
        $total = round($baseCost + $markupAmount, 2);

        // [Bug #5] Use per-adult flight price (not total), and include markup
        return [
            'hotel_total' => round($hotelTotal, 2),
            'flight_total' => round($flightTotal, 2),
            'base_cost' => round($baseCost, 2),
            'markup_percent' => $markup,
            'markup_amount' => $markupAmount,
            'total' => $total,
            'per_adult' => round(($hotelAdult + $flightPerAdult) * (1 + $markup / 100), 2),
        ];
    }

    private function getHotelPrice(Tour $tour, ?int $targetCurrencyId): ?float
    {
        $hotel = $tour->hotel;
        if (! $hotel || ! $hotel->price_per_person) {
            return null;
        }

        return $this->currencyConverter->convert(
            (float) $hotel->price_per_person,
            $hotel->currency_id,
            $targetCurrencyId,
        );
    }

    private function getFlightPrice(Tour $tour, ?int $targetCurrencyId): float
    {
        $total = 0;
        foreach ($tour->flights as $flight) {
            $total += $this->currencyConverter->convert(
                (float) $flight->price_adult,
                $flight->currency_id,
                $targetCurrencyId,
            );
        }

        return $total;
    }
}
