<?php

// File: app/Contracts/FlightProviderInterface.php

namespace App\Contracts;

use App\DTOs\FlightOffer;

interface FlightProviderInterface
{
    /**
     * Search for flight offers.
     *
     * @return FlightOffer[]
     */
    public function search(
        string $originIata,
        string $destinationIata,
        string $departureDate,
        int $passengerCount = 1,
    ): array;

    /**
     * Provider name identifier (e.g., 'amadeus', 'duffel', 'dummy').
     */
    public function name(): string;
}
