<?php

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Setting;
use App\Models\Tour;
use App\Services\CurrencyConverter;
use App\Services\TourPricingService;

beforeEach(function () {
    CurrencyConverter::clearCache();

    $this->currency = Currency::factory()->create([
        'code' => 'USD',
        'name_en' => 'US Dollar',
    ]);

    $this->converter = new CurrencyConverter();
    $this->service = new TourPricingService($this->converter);
});

test('calculates price correctly with hotel + flight + markup', function () {
    Setting::updateOrCreate(
        ['key' => 'tour_markup_percent'],
        [
        'value' => '15',
        'type' => 'decimal',
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 200.00,
        'currency_id' => $this->currency->id,
    ]);

    $flight = Flight::factory()->create([
        'price_adult' => 300.00,
        'currency_id' => $this->currency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
    ]);
    $tour->flights()->attach($flight, ['direction' => 'outbound']);

    $result = $this->service->recalculate($tour);

    // base = 200 + 300 = 500, markup 15% => 500 * 1.15 = 575.00
    expect($result)->toBe(575.00);
    expect($tour->fresh()->price)->toBe('575.00');
});

test('returns null when hotel has no price', function () {
    $hotel = Hotel::factory()->create([
        'price_per_person' => null,
        'currency_id' => $this->currency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => 10,
    ]);

    $result = $this->service->recalculate($tour);

    expect($result)->toBeNull();
});

test('returns null when base cost is zero (no hotel price and no flights)', function () {
    $hotel = Hotel::factory()->create([
        'price_per_person' => null,
        'currency_id' => $this->currency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => 10,
    ]);
    // No flights attached

    $result = $this->service->recalculate($tour);

    expect($result)->toBeNull();
});

test('applies per-tour markup override over global setting', function () {
    Setting::updateOrCreate(
        ['key' => 'tour_markup_percent'],
        [
        'value' => '15',
        'type' => 'decimal',
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 100.00,
        'currency_id' => $this->currency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => 25.00,
    ]);

    $result = $this->service->recalculate($tour);

    // base = 100, markup 25% => 100 * 1.25 = 125.00
    expect($result)->toBe(125.00);
    expect($tour->getEffectiveMarkupPercent())->toBe(25.00);
});

test('uses global markup when tour has no per-tour override', function () {
    Setting::updateOrCreate(
        ['key' => 'tour_markup_percent'],
        [
        'value' => '20',
        'type' => 'decimal',
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 100.00,
        'currency_id' => $this->currency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
    ]);

    $result = $this->service->recalculate($tour);

    // base = 100, global markup 20% => 100 * 1.20 = 120.00
    expect($result)->toBe(120.00);
    expect($tour->getEffectiveMarkupPercent())->toBe(20.00);
});

test('handles hotel with different currency than tour (needs CurrencyRate)', function () {
    $eurCurrency = Currency::factory()->create([
        'code' => 'EUR',
        'name_en' => 'Euro',
    ]);

    CurrencyRate::factory()->create([
        'from_currency_id' => $eurCurrency->id,
        'to_currency_id' => $this->currency->id,
        'rate' => 1.10,
        'date' => now(),
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 100.00,
        'currency_id' => $eurCurrency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => 10.00,
    ]);

    $result = $this->service->recalculate($tour);

    // hotel converted: 100 * 1.10 = 110.00 USD
    // base = 110.00, markup 10% => 110 * 1.10 = 121.00
    expect($result)->toBe(121.00);
});

test('recalculateForHotel updates all linked tours', function () {
    Setting::updateOrCreate(
        ['key' => 'tour_markup_percent'],
        [
        'value' => '10',
        'type' => 'decimal',
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 150.00,
        'currency_id' => $this->currency->id,
    ]);

    $tour1 = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
        'price' => 0,
    ]);
    $tour2 = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
        'price' => 0,
    ]);

    $this->service->recalculateForHotel($hotel->id);

    // base = 150, markup 10% => 150 * 1.10 = 165.00
    expect($tour1->fresh()->price)->toBe('165.00');
    expect($tour2->fresh()->price)->toBe('165.00');
});

test('recalculateForFlight updates all linked tours', function () {
    Setting::updateOrCreate(
        ['key' => 'tour_markup_percent'],
        [
        'value' => '10',
        'type' => 'decimal',
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 100.00,
        'currency_id' => $this->currency->id,
    ]);

    $flight = Flight::factory()->create([
        'price_adult' => 200.00,
        'currency_id' => $this->currency->id,
    ]);

    $tour1 = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
        'price' => 0,
    ]);
    $tour1->flights()->attach($flight, ['direction' => 'outbound']);

    $tour2 = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
        'price' => 0,
    ]);
    $tour2->flights()->attach($flight, ['direction' => 'outbound']);

    $this->service->recalculateForFlight($flight->id);

    // base = 100 + 200 = 300, markup 10% => 300 * 1.10 = 330.00
    expect($tour1->fresh()->price)->toBe('330.00');
    expect($tour2->fresh()->price)->toBe('330.00');
});

test('handles tour with no flights (hotel-only pricing)', function () {
    Setting::updateOrCreate(
        ['key' => 'tour_markup_percent'],
        [
        'value' => '10',
        'type' => 'decimal',
    ]);

    $hotel = Hotel::factory()->create([
        'price_per_person' => 250.00,
        'currency_id' => $this->currency->id,
    ]);

    $tour = Tour::factory()->create([
        'hotel_id' => $hotel->id,
        'currency_id' => $this->currency->id,
        'markup_percent' => null,
    ]);
    // No flights attached

    $result = $this->service->recalculate($tour);

    // base = 250, markup 10% => 250 * 1.10 = 275.00
    expect($result)->toBe(275.00);
    expect($tour->fresh()->price)->toBe('275.00');
});
