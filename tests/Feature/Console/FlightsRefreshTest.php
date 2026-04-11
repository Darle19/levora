<?php

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Flight;
use App\Models\FlightPath;
use App\Models\FlightPathLeg;
use App\Services\Flights\RapidApiFlightProvider;
use App\DTOs\FlightOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFlightOffer(string $airline, string $flightNumber, string $depDateTime, string $arrDateTime, int $priceCents, string $origin = 'XXX', string $dest = 'YYY'): FlightOffer
{
    return new FlightOffer(
        id: "test-{$airline}-{$flightNumber}",
        airlineCode: $airline,
        flightNumber: $flightNumber,
        originIata: $origin,
        destinationIata: $dest,
        departureAt: new DateTimeImmutable($depDateTime),
        arrivalAt: new DateTimeImmutable($arrDateTime),
        priceCents: $priceCents,
        currency: 'USD',
        seatsAvailable: 9,
        source: 'mock',
        localFlightId: null,
        providerFlightId: "mock-{$flightNumber}",
        rawData: [],
    );
}

beforeEach(function () {
    // Create basic reference data
    $this->country = Country::factory()->create(['code' => 'TR']);
    $this->uzCountry = Country::factory()->create(['code' => 'UZ']);
    $this->frCountry = Country::factory()->create(['code' => 'FR']);
    $this->azCountry = Country::factory()->create(['code' => 'AZ']);

    $this->istCity = City::factory()->create(['country_id' => $this->country->id, 'name_en' => 'Istanbul']);
    $this->tasCity = City::factory()->create(['country_id' => $this->uzCountry->id, 'name_en' => 'Tashkent']);
    $this->nceCity = City::factory()->create(['country_id' => $this->frCountry->id, 'name_en' => 'Nice']);
    $this->gydCity = City::factory()->create(['country_id' => $this->azCountry->id, 'name_en' => 'Baku']);

    $this->istAirport = Airport::factory()->create(['code' => 'IST', 'city_id' => $this->istCity->id]);
    $this->nceAirport = Airport::factory()->create(['code' => 'NCE', 'city_id' => $this->nceCity->id]);
    $this->tasAirport = Airport::factory()->create(['code' => 'TAS', 'city_id' => $this->tasCity->id]);
    $this->gydAirport = Airport::factory()->create(['code' => 'GYD', 'city_id' => $this->gydCity->id]);

    $this->tkAirline = Airline::factory()->create(['code' => 'TK', 'name' => 'Turkish Airlines']);
    $this->c2Airline = Airline::factory()->create(['code' => 'C2', 'name' => 'Centrum Air']);
    $this->j2Airline = Airline::factory()->create(['code' => 'J2', 'name' => 'Azerbaijan Airlines']);

    $this->usd = Currency::factory()->create(['code' => 'USD']);
});

it('groups flights by route-date-airline correctly', function () {
    $date = now()->addDays(30)->format('Y-m-d');

    // Two TK IST→NCE flights on same date (different flight numbers)
    $f1 = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->nceAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->nceCity->id,
        'flight_number' => '1813',
        'departure_date' => $date,
        'departure_time' => '07:10:00',
        'arrival_time' => '09:10:00',
        'is_active' => true,
    ]);

    $f2 = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->nceAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->nceCity->id,
        'flight_number' => '1815',
        'departure_date' => $date,
        'departure_time' => '16:20:00',
        'arrival_time' => '18:25:00',
        'is_active' => true,
    ]);

    $flights = Flight::where('is_active', true)->get()->keyBy('id');
    expect($flights->count())->toBe(2);

    $groupKey = fn (Flight $f) => $f->fromAirport->code . '-' . $f->toAirport->code . '-' . $f->departure_date->format('Y-m-d') . '-' . $f->airline->code;
    $groups = $flights->groupBy($groupKey);

    expect($groups->count())->toBe(1);
    expect($groups->first()->count())->toBe(2);
});

it('detects round-trip pairs across FlightPath legs', function () {
    $depDate = now()->addDays(30)->format('Y-m-d');
    $retDate = now()->addDays(34)->format('Y-m-d');

    $outbound = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->nceAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->nceCity->id,
        'flight_number' => '1813',
        'departure_date' => $depDate,
        'is_active' => true,
    ]);

    $return = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->nceAirport->id,
        'to_airport_id' => $this->istAirport->id,
        'origin_city_id' => $this->nceCity->id,
        'destination_city_id' => $this->istCity->id,
        'flight_number' => '1814',
        'departure_date' => $retDate,
        'is_active' => true,
    ]);

    $fp = FlightPath::create([
        'route_name' => 'Istanbul-Nice',
        'departure_date' => $depDate,
        'departure_city_id' => $this->istCity->id,
        'currency_id' => $this->usd->id,
        'nights' => 4,
        'is_available' => true,
    ]);

    FlightPathLeg::create([
        'flight_path_id' => $fp->id,
        'flight_id' => $outbound->id,
        'leg_order' => 1,
        'direction' => 'outbound',
    ]);
    FlightPathLeg::create([
        'flight_path_id' => $fp->id,
        'flight_id' => $return->id,
        'leg_order' => 2,
        'direction' => 'return',
    ]);

    // Use reflection to call private detectRoundTripPairs
    $command = new \App\Console\Commands\RefreshFlightData();
    $method = new ReflectionMethod($command, 'detectRoundTripPairs');
    $method->setAccessible(true);
    $pairs = $method->invoke($command, [$outbound->id, $return->id]);

    expect($pairs)->toHaveKey($outbound->id);
    expect($pairs[$outbound->id])->toBe($return->id);
});

it('excludes entire RT group from OW processing even with duplicate flights', function () {
    $depDate = now()->addDays(30)->format('Y-m-d');
    $retDate = now()->addDays(34)->format('Y-m-d');

    // Two outbound flights (same route+date+airline)
    $out1 = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->nceAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->nceCity->id,
        'flight_number' => '1813',
        'departure_date' => $depDate,
        'is_active' => true,
    ]);

    $out2 = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->nceAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->nceCity->id,
        'flight_number' => '1815',
        'departure_date' => $depDate,
        'is_active' => true,
    ]);

    // Two return flights
    $ret1 = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->nceAirport->id,
        'to_airport_id' => $this->istAirport->id,
        'origin_city_id' => $this->nceCity->id,
        'destination_city_id' => $this->istCity->id,
        'flight_number' => '1814',
        'departure_date' => $retDate,
        'is_active' => true,
    ]);

    $ret2 = Flight::factory()->create([
        'airline_id' => $this->tkAirline->id,
        'from_airport_id' => $this->nceAirport->id,
        'to_airport_id' => $this->istAirport->id,
        'origin_city_id' => $this->nceCity->id,
        'destination_city_id' => $this->istCity->id,
        'flight_number' => '1816',
        'departure_date' => $retDate,
        'is_active' => true,
    ]);

    // Only out1 + ret1 are in a FlightPath
    $fp = FlightPath::create([
        'route_name' => 'IST-NCE',
        'departure_date' => $depDate,
        'departure_city_id' => $this->istCity->id,
        'currency_id' => $this->usd->id,
        'nights' => 4,
        'is_available' => true,
    ]);
    FlightPathLeg::create(['flight_path_id' => $fp->id, 'flight_id' => $out1->id, 'leg_order' => 1, 'direction' => 'outbound']);
    FlightPathLeg::create(['flight_path_id' => $fp->id, 'flight_id' => $ret1->id, 'leg_order' => 2, 'direction' => 'return']);

    // Mock the provider to return a RT offer
    $mockProvider = Mockery::mock(RapidApiFlightProvider::class);
    $mockProvider->shouldReceive('searchRoundTripOutbound')
        ->once()
        ->andReturn([
            makeFlightOffer('TK', '1813', "{$depDate}T07:10:00", "{$depDate}T09:10:00", 41200),
        ]);
    // OW should NOT be called because RT group covers all 4 flights
    $mockProvider->shouldNotReceive('search');

    $this->app->instance(RapidApiFlightProvider::class, $mockProvider);

    $this->artisan('flights:refresh', ['--days' => 60])->assertSuccessful();

    // All 4 flights should have price_adult = 206 (412/2)
    expect((float) $out1->fresh()->price_adult)->toBe(206.0);
    expect((float) $out2->fresh()->price_adult)->toBe(206.0);
    expect((float) $ret1->fresh()->price_adult)->toBe(206.0);
    expect((float) $ret2->fresh()->price_adult)->toBe(206.0);

    // Flight number on outbound should be updated
    expect($out1->fresh()->flight_number)->toBe('1813');
    expect($out2->fresh()->flight_number)->toBe('1813');
});

it('updates J2 flight with cheapest daytime option (number, time, price)', function () {
    $date = '2026-05-04';

    // Existing DB flight: J2 76 at 12:00, $180
    $flight = Flight::factory()->create([
        'airline_id' => $this->j2Airline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->gydAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->gydCity->id,
        'flight_number' => '76',
        'departure_date' => $date,
        'departure_time' => '12:00:00',
        'arrival_time' => '15:50:00',
        'price_adult' => 180.00,
        'is_active' => true,
    ]);

    // Mock provider to return multiple J2 options — cheapest daytime = J2 8104 @ 14:00 $150
    $mockProvider = Mockery::mock(RapidApiFlightProvider::class);
    $mockProvider->shouldReceive('search')
        ->once()
        ->with('IST', 'GYD', $date, 1, 'J2')
        ->andReturn([
            makeFlightOffer('J2', '76', "{$date}T12:00:00", "{$date}T15:50:00", 18000),
            makeFlightOffer('J2', '8104', "{$date}T14:00:00", "{$date}T17:50:00", 15000),
            makeFlightOffer('J2', '78', "{$date}T22:50:00", "{$date}T02:40:00", 12000), // night, excluded
        ]);
    $mockProvider->shouldNotReceive('searchRoundTripOutbound');

    $this->app->instance(RapidApiFlightProvider::class, $mockProvider);

    $this->artisan('flights:refresh', ['--days' => 60])->assertSuccessful();

    $fresh = $flight->fresh();

    // Should pick J2 8104 (cheapest DAYTIME, not J2 78 which is night)
    expect($fresh->flight_number)->toBe('8104');
    expect($fresh->departure_time)->toBe('14:00:00');
    expect($fresh->arrival_time)->toBe('17:50:00');
    expect((float) $fresh->price_adult)->toBe(150.0);
});

it('searches multiple airports per city and picks cheapest across all airports', function () {
    $date = '2026-05-08';

    // Istanbul has TWO airports: IST and SAW
    $sawAirport = Airport::factory()->create(['code' => 'SAW', 'city_id' => $this->istCity->id, 'is_active' => true]);

    // DB flight: J2 76 IST→GYD at $208
    $flight = Flight::factory()->create([
        'airline_id' => $this->j2Airline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->gydAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->gydCity->id,
        'flight_number' => '76',
        'departure_date' => $date,
        'departure_time' => '12:00:00',
        'arrival_time' => '15:50:00',
        'price_adult' => 208.00,
        'is_active' => true,
    ]);

    // Mock: IST→GYD returns J2 76 at $208, SAW→GYD returns J2 8104 at $157 (cheaper)
    $mockProvider = Mockery::mock(RapidApiFlightProvider::class);
    $mockProvider->shouldReceive('search')
        ->with('IST', 'GYD', $date, 1, 'J2')
        ->andReturn([
            makeFlightOffer('J2', '76', "{$date}T12:00:00", "{$date}T15:50:00", 20800, 'IST', 'GYD'),
        ]);
    $mockProvider->shouldReceive('search')
        ->with('SAW', 'GYD', $date, 1, 'J2')
        ->andReturn([
            makeFlightOffer('J2', '8104', "{$date}T10:40:00", "{$date}T14:30:00", 15700, 'SAW', 'GYD'),
            makeFlightOffer('J2', '8106', "{$date}T15:55:00", "{$date}T19:45:00", 15700, 'SAW', 'GYD'),
        ]);
    $mockProvider->shouldNotReceive('searchRoundTripOutbound');

    $this->app->instance(RapidApiFlightProvider::class, $mockProvider);
    $this->artisan('flights:refresh', ['--days' => 60])->assertSuccessful();

    // Note: SAW factory will overwrite existing SAW airport, need to refetch
    $sawAirport = Airport::where('code', 'SAW')->first();

    $fresh = $flight->fresh();
    expect($fresh->flight_number)->toBe('8104');
    expect((float) $fresh->price_adult)->toBe(157.0);
    expect($fresh->departure_time)->toBe('10:40:00');
    expect($fresh->from_airport_id)->toBe($sawAirport->id); // updated to SAW
});

it('skips night flights even if cheaper', function () {
    $date = '2026-05-04';

    $flight = Flight::factory()->create([
        'airline_id' => $this->j2Airline->id,
        'from_airport_id' => $this->istAirport->id,
        'to_airport_id' => $this->gydAirport->id,
        'origin_city_id' => $this->istCity->id,
        'destination_city_id' => $this->gydCity->id,
        'flight_number' => '76',
        'departure_date' => $date,
        'departure_time' => '12:00:00',
        'arrival_time' => '15:50:00',
        'price_adult' => 200.00,
        'is_active' => true,
    ]);

    $mockProvider = Mockery::mock(RapidApiFlightProvider::class);
    $mockProvider->shouldReceive('search')
        ->once()
        ->andReturn([
            makeFlightOffer('J2', '78', "{$date}T22:50:00", "{$date}T02:40:00", 5000),  // $50 but night
            makeFlightOffer('J2', '76', "{$date}T12:00:00", "{$date}T15:50:00", 18000), // $180 daytime
        ]);

    $this->app->instance(RapidApiFlightProvider::class, $mockProvider);
    $this->artisan('flights:refresh', ['--days' => 60])->assertSuccessful();

    $fresh = $flight->fresh();
    expect($fresh->flight_number)->toBe('76');
    expect((float) $fresh->price_adult)->toBe(180.0);
});
