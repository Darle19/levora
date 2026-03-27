<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Tour;

class TourPricingService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
    ) {}

    /**
     * Calculate and persist the price for a single tour.
     *
     * Formula (per person):
     *   SUM(flight ticket prices)
     * + SUM(hotel_price_per_room / 2 × nights_in_city)
     * + SUM(city transfer fees)
     * + hidden_fee (admin setting, default $60)
     * + agent_fee (admin setting, default $50)
     */
    public function recalculate(Tour $tour): ?string
    {
        $tour->loadMissing([
            'hotel', 'flights', 'tourPrices',
            'stays', 'stays.hotel', 'stays.currency',
            'additionalServices',
        ]);

        $targetCurrencyId = $tour->currency_id;

        $flightPrice = $this->getFlightPrice($tour, $targetCurrencyId);
        $hotelPrice = $this->getHotelPricePerPerson($tour, $targetCurrencyId);
        $transferPrice = $this->getTransferPrice($tour, $targetCurrencyId);
        $hiddenFee = (string) Setting::getValue('tour_hidden_fee', 60);
        $agentFee = (string) Setting::getValue('tour_agent_fee', 50);

        $total = '0';
        $total = bcadd($total, $flightPrice, 2);
        $total = bcadd($total, $hotelPrice ?? '0', 2);
        $total = bcadd($total, $transferPrice, 2);
        $total = bcadd($total, $hiddenFee, 2);
        $total = bcadd($total, $agentFee, 2);

        if (bccomp($total, '0', 2) <= 0) {
            return null;
        }

        $tour->updateQuietly(['price' => $total]);

        return $total;
    }

    /**
     * Recalculate prices for all tours linked to a specific hotel.
     */
    public function recalculateForHotel(int $hotelId): void
    {
        Tour::where('hotel_id', $hotelId)
            ->orWhereHas('stays', fn ($q) => $q->where('hotel_id', $hotelId))
            ->each(fn (Tour $tour) => $this->recalculate($tour));
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
     * Recalculate ALL tour prices (used when fees change).
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
     * Get the price breakdown for display.
     */
    public function getBreakdown(Tour $tour): array
    {
        $tour->loadMissing([
            'hotel', 'flights', 'stays', 'stays.hotel', 'stays.currency',
            'additionalServices',
        ]);
        $targetCurrencyId = $tour->currency_id;

        $flightPrice = $this->getFlightPrice($tour, $targetCurrencyId);
        $hotelPrice = $this->getHotelPricePerPerson($tour, $targetCurrencyId) ?? '0';
        $transferPrice = $this->getTransferPrice($tour, $targetCurrencyId);
        $hiddenFee = (string) Setting::getValue('tour_hidden_fee', 60);
        $agentFee = (string) Setting::getValue('tour_agent_fee', 50);

        $total = '0';
        $total = bcadd($total, $flightPrice, 2);
        $total = bcadd($total, $hotelPrice, 2);
        $total = bcadd($total, $transferPrice, 2);
        $total = bcadd($total, $hiddenFee, 2);
        $total = bcadd($total, $agentFee, 2);

        return [
            'flight_price' => $flightPrice,
            'hotel_price_per_person' => $hotelPrice,
            'transfer_price' => $transferPrice,
            'hidden_fee' => $hiddenFee,
            'agent_fee' => $agentFee,
            'total_price' => $total,
        ];
    }

    /**
     * Calculate booking price for specific room type and passengers.
     */
    public function calculateBookingPrice(
        Tour $tour,
        int $roomTypeId,
        int $adults,
        int $children = 0,
        int $infants = 0,
    ): ?array {
        $tour->loadMissing([
            'tourPrices', 'flights', 'stays', 'stays.hotel', 'stays.currency',
            'additionalServices',
        ]);

        $tourPrice = $tour->tourPrices
            ->where('room_type_id', $roomTypeId)
            ->where('is_active', true)
            ->first();

        if (! $tourPrice) {
            return null;
        }

        $targetCurrencyId = $tour->currency_id;
        $hiddenFee = (string) Setting::getValue('tour_hidden_fee', 60);
        $agentFee = (string) Setting::getValue('tour_agent_fee', 50);
        $totalPax = $adults + $children + $infants;

        // Hotel costs per room type
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
            bcmul($hotelInfant, (string) $infants, 2), 2
        );

        // Flight costs
        $flightTotal = '0';
        foreach ($tour->flights as $flight) {
            $adultPrice = $this->currencyConverter->convert((string) $flight->price_adult, $flight->currency_id, $targetCurrencyId);
            $childPrice = $this->currencyConverter->convert((string) ($flight->price_child ?? $flight->price_adult), $flight->currency_id, $targetCurrencyId);
            $infantPrice = $this->currencyConverter->convert((string) ($flight->price_infant ?? 0), $flight->currency_id, $targetCurrencyId);

            $flightSegment = bcadd(
                bcadd(bcmul($adultPrice, (string) $adults, 2), bcmul($childPrice, (string) $children, 2), 2),
                bcmul($infantPrice, (string) $infants, 2), 2
            );
            $flightTotal = bcadd($flightTotal, $flightSegment, 2);
        }

        // Transfer costs (per person × all pax)
        $transferPerPerson = $this->getTransferPrice($tour, $targetCurrencyId);
        $transferTotal = bcmul($transferPerPerson, (string) $totalPax, 2);

        // Fees × all pax
        $feesTotal = bcmul(bcadd($hiddenFee, $agentFee, 2), (string) $totalPax, 2);

        $total = bcadd(bcadd($hotelTotal, $flightTotal, 2), bcadd($transferTotal, $feesTotal, 2), 2);

        // Per adult: hotel + flights + transfer + fees
        $flightPerAdult = '0';
        foreach ($tour->flights as $flight) {
            $flightPerAdult = bcadd($flightPerAdult, $this->currencyConverter->convert(
                (string) $flight->price_adult, $flight->currency_id, $targetCurrencyId
            ), 2);
        }
        $perAdult = bcadd(bcadd($hotelAdult, $flightPerAdult, 2), bcadd($transferPerPerson, bcadd($hiddenFee, $agentFee, 2), 2), 2);

        return [
            'hotel_total' => $hotelTotal,
            'flight_total' => $flightTotal,
            'transfer_total' => $transferTotal,
            'fees_total' => $feesTotal,
            'total' => $total,
            'per_adult' => $perAdult,
        ];
    }

    /**
     * Hotel price per person: room_price / 2 × nights per stay.
     */
    private function getHotelPricePerPerson(Tour $tour, ?int $targetCurrencyId): ?string
    {
        if ($tour->stays->isNotEmpty()) {
            $total = '0';
            foreach ($tour->stays as $stay) {
                $roomPrice = $stay->price_per_person ?? $stay->hotel?->price_per_person;
                if (! $roomPrice) {
                    continue;
                }
                $currencyId = $stay->currency_id ?? $stay->hotel?->currency_id;
                $converted = $this->currencyConverter->convert((string) $roomPrice, $currencyId, $targetCurrencyId);
                // Room price / 2 (dbl room → per person) × nights
                $perPerson = bcdiv($converted, '2', 2);
                $stayTotal = bcmul($perPerson, (string) $stay->nights, 2);
                $total = bcadd($total, $stayTotal, 2);
            }

            return bccomp($total, '0', 2) > 0 ? $total : null;
        }

        // Single hotel fallback
        $hotel = $tour->hotel;
        if (! $hotel || ! $hotel->price_per_person) {
            return null;
        }

        $roomPrice = $this->currencyConverter->convert(
            (string) $hotel->price_per_person, $hotel->currency_id, $targetCurrencyId
        );

        return bcdiv($roomPrice, '2', 2);
    }

    /**
     * Sum all flight ticket prices per person (outbound + return).
     */
    private function getFlightPrice(Tour $tour, ?int $targetCurrencyId): string
    {
        $total = '0';
        foreach ($tour->flights as $flight) {
            $converted = $this->currencyConverter->convert(
                (string) $flight->price_adult, $flight->currency_id, $targetCurrencyId
            );
            $total = bcadd($total, $converted, 2);
        }

        return $total;
    }

    /**
     * Sum transfer fees from included additional services (per person).
     */
    private function getTransferPrice(Tour $tour, ?int $targetCurrencyId): string
    {
        $total = '0';
        foreach ($tour->additionalServices as $service) {
            if ($service->service_type !== 'transfer') {
                continue;
            }
            if (! $service->pivot->is_included) {
                continue;
            }
            $price = $service->pivot->price_override ?? $service->price;
            if ($price) {
                $converted = $this->currencyConverter->convert(
                    (string) $price, $service->currency_id, $targetCurrencyId
                );
                $total = bcadd($total, $converted, 2);
            }
        }

        return $total;
    }
}
