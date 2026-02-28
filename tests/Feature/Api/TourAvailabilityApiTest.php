<?php

use App\Models\Country;
use App\Models\Currency;
use App\Models\Resort;
use App\Models\Tour;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->country = Country::factory()->create(['is_active' => true]);
});

test('available-dates returns dates for country', function () {
    $tour = Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'date_from' => now()->addDays(10),
    ]);

    $response = $this->getJson('/api/tours/available-dates?country_id=' . $this->country->id);

    $response->assertStatus(200);
    $response->assertJsonStructure(['dates']);

    $dates = $response->json('dates');
    expect($dates)->toBeArray();
    expect($dates)->toContain($tour->date_from->format('Y-m-d'));
});

test('nights-range returns min and max', function () {
    Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'nights' => 5,
    ]);

    Tour::factory()->create([
        'country_id' => $this->country->id,
        'currency_id' => $this->currency->id,
        'is_available' => true,
        'nights' => 12,
    ]);

    $response = $this->getJson('/api/tours/nights-range?country_id=' . $this->country->id);

    $response->assertStatus(200);
    $response->assertJsonStructure(['min', 'max']);
    $response->assertJson(['min' => 5, 'max' => 12]);
});

test('resorts returns filtered by country_id', function () {
    $resort = Resort::factory()->create([
        'country_id' => $this->country->id,
        'is_active' => true,
    ]);

    $otherCountry = Country::factory()->create(['is_active' => true]);
    $otherResort = Resort::factory()->create([
        'country_id' => $otherCountry->id,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/resorts?country_id=' . $this->country->id);

    $response->assertStatus(200);
    $response->assertJsonStructure(['resorts']);

    $resortIds = collect($response->json('resorts'))->pluck('id')->all();
    expect($resortIds)->toContain($resort->id);
    expect($resortIds)->not->toContain($otherResort->id);
});

test('all endpoints return JSON', function () {
    $response = $this->getJson('/api/tours/available-dates');
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');

    $response = $this->getJson('/api/tours/nights-range');
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');

    $response = $this->getJson('/api/resorts?country_id=' . $this->country->id);
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');
});
