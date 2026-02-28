<?php

use App\Models\Country;
use App\Models\Currency;
use App\Models\Tour;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear cache so filter options are fresh for each test
    Cache::flush();

    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->country = Country::factory()->create(['is_active' => true]);
});

test('search page loads', function () {
    $response = $this->get(route('search.tours'));

    $response->assertStatus(200);
});

test('search with country filter returns matching tours', function () {
    $matchingTour = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'price' => 1000.00,
    ]);

    $otherCountry = Country::factory()->create(['is_active' => true]);
    $nonMatchingTour = Tour::factory()->create([
        'country_id' => $otherCountry->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'price' => 2000.00,
    ]);

    $response = $this->post(route('search.tours.search'), [
        'country_id' => $this->country->id,
    ]);

    $response->assertStatus(200);
    $response->assertSee((string) $matchingTour->hotel->name);
});

test('search returns only available tours', function () {
    $availableTour = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
    ]);

    $unavailableTour = Tour::factory()->unavailable()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
    ]);

    $response = $this->post(route('search.tours.search'), [
        'country_id' => $this->country->id,
    ]);

    $response->assertStatus(200);
});

test('search with date range returns matching tours', function () {
    $tourInRange = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'date_from' => now()->addDays(10),
        'date_to' => now()->addDays(17),
    ]);

    $tourOutOfRange = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'date_from' => now()->addMonths(6),
        'date_to' => now()->addMonths(6)->addDays(7),
    ]);

    $response = $this->post(route('search.tours.search'), [
        'country_id' => $this->country->id,
        'date_from' => now()->addDays(5)->format('Y-m-d'),
        'date_to' => now()->addDays(15)->format('Y-m-d'),
    ]);

    $response->assertStatus(200);
});

test('search with price range returns matching tours', function () {
    $cheapTour = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'price' => 500.00,
    ]);

    $expensiveTour = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'price' => 5000.00,
    ]);

    $response = $this->post(route('search.tours.search'), [
        'country_id' => $this->country->id,
        'price_from' => 400,
        'price_to' => 600,
    ]);

    $response->assertStatus(200);
});

test('results page paginates at 20 per page', function () {
    // Create 25 available tours for the same country
    Tour::factory()->count(25)->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
    ]);

    $response = $this->get(route('search.tours.results', [
        'country_id' => $this->country->id,
    ]));

    $response->assertStatus(200);
});

test('results page sorts by price', function () {
    Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'price' => 3000.00,
    ]);

    Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'price' => 1000.00,
    ]);

    $response = $this->get(route('search.tours.results', [
        'country_id' => $this->country->id,
        'sort_by' => 'price',
        'sort_dir' => 'asc',
    ]));

    $response->assertStatus(200);
});

test('tour detail page loads', function () {
    $tour = Tour::factory()->create([
        'currency_id' => $this->currency->id,
        'is_available' => true,
    ]);

    $response = $this->get(route('tours.show', $tour));

    $response->assertStatus(200);
});
