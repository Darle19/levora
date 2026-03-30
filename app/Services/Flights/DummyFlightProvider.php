<?php

// File: app/Services/Flights/DummyFlightProvider.php

namespace App\Services\Flights;

use App\Contracts\FlightProviderInterface;
use App\DTOs\FlightOffer;
use DateTimeImmutable;

/**
 * Dummy provider that returns fake flight offers.
 * Use this as a reference implementation for real providers (Amadeus, Duffel, etc).
 */
class DummyFlightProvider implements FlightProviderInterface
{
    public function search(
        string $originIata,
        string $destinationIata,
        string $departureDate,
        int $passengerCount = 1,
    ): array {
        $base = crc32($originIata . $destinationIata . $departureDate);
        $offers = [];

        // Generate 3 fake offers with different times and prices
        $variants = [
            ['hour' => 6, 'airline' => 'DM', 'num' => '100', 'price' => 15000],
            ['hour' => 13, 'airline' => 'DM', 'num' => '200', 'price' => 22000],
            ['hour' => 20, 'airline' => 'DM', 'num' => '300', 'price' => 18500],
        ];

        foreach ($variants as $i => $v) {
            $depTime = new DateTimeImmutable("{$departureDate} {$v['hour']}:00:00");
            $arrTime = $depTime->modify('+3 hours +30 minutes');
            $priceCents = $v['price'] + (abs($base) % 5000);

            $offers[] = new FlightOffer(
                id: "dummy-{$originIata}-{$destinationIata}-{$departureDate}-{$i}",
                airlineCode: $v['airline'],
                flightNumber: $v['num'],
                originIata: $originIata,
                destinationIata: $destinationIata,
                departureAt: $depTime,
                arrivalAt: $arrTime,
                priceCents: $priceCents,
                currency: 'USD',
                seatsAvailable: rand(5, 180),
                source: $this->name(),
                localFlightId: null,
                providerFlightId: "dummy-{$i}-{$base}",
                rawData: ['provider' => 'dummy', 'generated' => true],
            );
        }

        return $offers;
    }

    public function name(): string
    {
        return 'dummy';
    }
}
