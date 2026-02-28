<?php

use App\DTOs\FlightOfferDto;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Currency;
use App\Models\Flight;

test('creates from local Flight model with correct field mapping', function () {
    $currency = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);
    $airline = Airline::factory()->create(['code' => 'TK', 'name' => 'Turkish Airlines']);
    $fromAirport = Airport::factory()->create(['code' => 'TAS', 'name_en' => 'Tashkent Airport']);
    $toAirport = Airport::factory()->create(['code' => 'IST', 'name_en' => 'Istanbul Airport']);

    $flight = Flight::factory()->create([
        'airline_id' => $airline->id,
        'from_airport_id' => $fromAirport->id,
        'to_airport_id' => $toAirport->id,
        'currency_id' => $currency->id,
        'flight_number' => 'TK123',
        'departure_date' => '2026-03-15',
        'departure_time' => '10:30',
        'arrival_date' => '2026-03-15',
        'arrival_time' => '14:45',
        'price_adult' => 450.00,
        'price_child' => 350.00,
        'price_infant' => 50.00,
        'available_seats' => 120,
        'class_type' => 'economy',
    ]);

    $dto = FlightOfferDto::fromLocalFlight($flight->load(['airline', 'fromAirport', 'toAirport', 'currency']));

    expect($dto->id)->toBe((string) $flight->id);
    expect($dto->source)->toBe('local');
    expect($dto->airline)->toBe('TK');
    expect($dto->airlineName)->toBe('Turkish Airlines');
    expect($dto->flightNumber)->toBe('TK123');
    expect($dto->origin)->toBe('TAS');
    expect($dto->originName)->toBe('Tashkent Airport');
    expect($dto->destination)->toBe('IST');
    expect($dto->destinationName)->toBe('Istanbul Airport');
    expect($dto->departureDate)->toBe('2026-03-15');
    expect($dto->departureTime)->toBe('10:30');
    expect($dto->arrivalDate)->toBe('2026-03-15');
    expect($dto->arrivalTime)->toBe('14:45');
    expect($dto->priceTotal)->toBe(450.00);
    expect($dto->pricePerAdult)->toBe(450.00);
    expect($dto->pricePerChild)->toBe(350.00);
    expect($dto->pricePerInfant)->toBe(50.00);
    expect($dto->currency)->toBe('USD');
    expect($dto->availableSeats)->toBe(120);
    expect($dto->cabinClass)->toBe('economy');
    expect($dto->isAmadeus)->toBeFalse();
});

test('toArray returns all expected keys', function () {
    $currency = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);
    $flight = Flight::factory()->create([
        'currency_id' => $currency->id,
        'price_adult' => 200.00,
        'price_child' => 150.00,
        'price_infant' => null,
    ]);

    $dto = FlightOfferDto::fromLocalFlight($flight->load(['airline', 'fromAirport', 'toAirport', 'currency']));
    $array = $dto->toArray();

    $expectedKeys = [
        'id', 'source', 'airline', 'airlineName', 'flightNumber',
        'origin', 'originName', 'destination', 'destinationName',
        'departureDate', 'departureTime', 'arrivalDate', 'arrivalTime',
        'duration', 'stops', 'priceTotal', 'currency', 'pricePerAdult',
        'pricePerChild', 'pricePerInfant', 'availableSeats', 'cabinClass',
        'returnDepartureDate', 'returnDepartureTime', 'returnArrivalDate',
        'returnArrivalTime', 'returnDuration', 'returnStops',
        'isAmadeus', 'amadeusOfferId',
    ];

    expect($array)->toHaveKeys($expectedKeys);
    expect($array['source'])->toBe('local');
    expect($array['isAmadeus'])->toBeFalse();
    expect($array['returnDepartureDate'])->toBeNull();
    expect($array['amadeusOfferId'])->toBeNull();
});

test('formatDuration converts ISO format correctly', function () {
    // formatDuration is private static, so we test it indirectly through fromAmadeus
    // But we can also use Reflection for a direct test
    $method = new ReflectionMethod(FlightOfferDto::class, 'formatDuration');

    expect($method->invoke(null, 'PT2H30M'))->toBe('2h 30m');
    expect($method->invoke(null, 'PT5H0M'))->toBe('5h 0m');
    expect($method->invoke(null, 'PT45M'))->toBe('45m');
    expect($method->invoke(null, 'PT12H'))->toBe('12h 0m');
    expect($method->invoke(null, ''))->toBe('');
});
