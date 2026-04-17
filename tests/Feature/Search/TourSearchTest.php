<?php

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Flight;
use App\Models\FlightPath;
use App\Models\FlightPathLeg;
use App\Models\FlightPathStay;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\Resort;
use App\Services\TourPriceCalculator;
use App\Services\TourSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Cache::flush();
    TourPriceCalculator::clearPricingContext();

    $this->usd = Currency::factory()->create(['code' => 'USD']);

    $this->turkey = Country::factory()->create(['is_active' => true, 'name_en' => 'Turkey']);
    $this->france = Country::factory()->create(['is_active' => true, 'name_en' => 'France']);
    $this->azerbaijan = Country::factory()->create(['is_active' => true, 'name_en' => 'Azerbaijan']);

    $this->tashkent = City::factory()->create(['country_id' => Country::factory()->create()->id, 'is_departure' => true, 'is_active' => true]);
    $this->istanbul = City::factory()->create(['country_id' => $this->turkey->id, 'is_active' => true]);
    $this->nice = City::factory()->create(['country_id' => $this->france->id, 'is_active' => true]);
    $this->baku = City::factory()->create(['country_id' => $this->azerbaijan->id, 'is_active' => true]);
});

/**
 * Build a FlightPath with stays + one cheap flight leg + one hotel per stay city.
 * Kept here (not as a factory) because FlightPath is only created via tests and seeders.
 */
function makeFlightPath(string $routeName, City $from, array $stayCities, int $nightsPerStay = 3): FlightPath
{
    $currency = test()->usd;
    $airline = Airline::factory()->create();
    $airport = Airport::factory()->create();
    $resort = Resort::factory()->create();
    $category = HotelCategory::factory()->create();

    $path = FlightPath::create([
        'route_name' => $routeName,
        'departure_date' => now()->addDays(30)->toDateString(),
        'departure_city_id' => $from->id,
        'total_price' => 500,
        'currency_id' => $currency->id,
        'nights' => count($stayCities) * $nightsPerStay,
        'is_available' => true,
    ]);

    $flight = Flight::factory()->create([
        'airline_id' => $airline->id,
        'from_airport_id' => $airport->id,
        'to_airport_id' => $airport->id,
        'currency_id' => $currency->id,
        'price_adult' => 200,
        'available_seats' => 50,
    ]);
    FlightPathLeg::create([
        'flight_path_id' => $path->id,
        'flight_id' => $flight->id,
        'leg_order' => 1,
        'direction' => 'outbound',
    ]);

    foreach ($stayCities as $i => $city) {
        FlightPathStay::create([
            'flight_path_id' => $path->id,
            'city_id' => $city->id,
            'stay_order' => $i + 1,
            'nights' => $nightsPerStay,
        ]);
        Hotel::factory()->create([
            'city_id' => $city->id,
            'resort_id' => $resort->id,
            'hotel_category_id' => $category->id,
            'currency_id' => $currency->id,
            'price_per_person' => 100,
            'is_active' => true,
        ]);
    }

    return $path;
}

test('search page loads successfully', function () {
    $response = $this->get(route('search.tours'));
    $response->assertStatus(200);
});

test('country filter returns only flight paths whose stays include that country', function () {
    makeFlightPath('Istanbul + Nice', $this->tashkent, [$this->istanbul, $this->nice]);
    makeFlightPath('Istanbul + Baku', $this->tashkent, [$this->istanbul, $this->baku]);

    $svc = app(TourSearchService::class);

    $france = $svc->search(['country_id' => $this->france->id]);
    $azerbaijan = $svc->search(['country_id' => $this->azerbaijan->id]);
    $turkey = $svc->search(['country_id' => $this->turkey->id]);

    expect(array_unique(array_map(fn ($r) => $r->flight_path->route_name, $france)))->toBe(['Istanbul + Nice']);
    expect(array_unique(array_map(fn ($r) => $r->flight_path->route_name, $azerbaijan)))->toBe(['Istanbul + Baku']);
    // Turkey is in BOTH routes — both should appear
    expect(array_unique(array_map(fn ($r) => $r->flight_path->route_name, $turkey)))
        ->toContain('Istanbul + Nice')
        ->toContain('Istanbul + Baku');
});

test('buildRoutes exposes country_ids for every stay country', function () {
    makeFlightPath('Istanbul + Nice', $this->tashkent, [$this->istanbul, $this->nice]);

    $routes = app(TourSearchService::class)->getFilterOptions()['tourRoutes'];
    $nice = collect($routes)->firstWhere('label', 'Istanbul + Nice');

    expect($nice['filters']['country_ids'])
        ->toContain($this->turkey->id)
        ->toContain($this->france->id);
});

test('country filter with no matching routes returns empty', function () {
    makeFlightPath('Istanbul + Nice', $this->tashkent, [$this->istanbul, $this->nice]);

    $results = app(TourSearchService::class)->search(['country_id' => $this->azerbaijan->id]);

    expect($results)->toBeEmpty();
});

test('pricing context is cached across combos in a single search', function () {
    // Two distinct flight paths so the search builds multiple combos
    makeFlightPath('Istanbul + Nice', $this->tashkent, [$this->istanbul, $this->nice]);
    makeFlightPath('Istanbul + Baku', $this->tashkent, [$this->istanbul, $this->baku]);

    // Warm factories / caches we don't care about measuring
    app(TourSearchService::class)->getFilterOptions();
    TourPriceCalculator::clearPricingContext();

    DB::enableQueryLog();
    DB::flushQueryLog();
    $results = app(TourSearchService::class)->search([]);
    $log = DB::getQueryLog();

    $settings = collect($log)->filter(fn ($q) => str_contains($q['query'], 'from "settings"'))->count();
    $services = collect($log)->filter(fn ($q) => str_contains($q['query'], 'from "additional_services"'))->count();

    expect($results)->not->toBeEmpty();
    // Regardless of combo count, pricing context must be fetched at most once per search
    expect($settings)->toBeLessThanOrEqual(2); // hidden_fee + agent_fee, each fetched once
    expect($services)->toBe(1);
});
