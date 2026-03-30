<?php

// File: app/DTOs/TourTemplateSummary.php

namespace App\DTOs;

final readonly class TourTemplateSummary
{
    /**
     * @param array<array{leg_order: int, from: string, to: string, date: string, flight: ?string, price_cents: ?int}> $legs
     * @param array<array{city: string, nights: int, check_in: string, check_out: string}> $stays
     */
    public function __construct(
        public int $id,
        public string $routeName,
        public string $status,
        public string $baseCurrency,
        public int $marginPercent,
        public int $totalNights,
        public array $legs,
        public array $stays,
        public int $totalFlightCostCents,
        public bool $allFlightsSelected,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'route_name' => $this->routeName,
            'status' => $this->status,
            'base_currency' => $this->baseCurrency,
            'margin_percent' => $this->marginPercent,
            'total_nights' => $this->totalNights,
            'legs' => $this->legs,
            'stays' => $this->stays,
            'total_flight_cost_cents' => $this->totalFlightCostCents,
            'all_flights_selected' => $this->allFlightsSelected,
        ];
    }
}
