<?php

// File: app/DTOs/FlightOffer.php

namespace App\DTOs;

use DateTimeImmutable;

final readonly class FlightOffer
{
    public function __construct(
        public string $id,
        public string $airlineCode,
        public string $flightNumber,
        public string $originIata,
        public string $destinationIata,
        public DateTimeImmutable $departureAt,
        public DateTimeImmutable $arrivalAt,
        public int $priceCents,
        public string $currency,
        public int $seatsAvailable,
        public string $source,           // 'local_db' or provider name
        public ?int $localFlightId,      // FK to flights table if source=local_db
        public ?string $providerFlightId,
        public array $rawData = [],
    ) {}

    public function durationMinutes(): int
    {
        return (int) (($this->arrivalAt->getTimestamp() - $this->departureAt->getTimestamp()) / 60);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'airline_code' => $this->airlineCode,
            'flight_number' => $this->flightNumber,
            'origin_iata' => $this->originIata,
            'destination_iata' => $this->destinationIata,
            'departure_at' => $this->departureAt->format('Y-m-d\TH:i:s'),
            'arrival_at' => $this->arrivalAt->format('Y-m-d\TH:i:s'),
            'price_cents' => $this->priceCents,
            'currency' => $this->currency,
            'seats_available' => $this->seatsAvailable,
            'duration_minutes' => $this->durationMinutes(),
            'source' => $this->source,
            'local_flight_id' => $this->localFlightId,
            'provider_flight_id' => $this->providerFlightId,
        ];
    }
}
