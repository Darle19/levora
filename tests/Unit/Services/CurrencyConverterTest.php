<?php

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Services\CurrencyConverter;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    CurrencyConverter::clearCache();
    $this->converter = new CurrencyConverter();
});

test('converts using direct rate', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);
    $eur = Currency::factory()->create(['code' => 'EUR', 'name_en' => 'Euro']);

    CurrencyRate::factory()->create([
        'from_currency_id' => $usd->id,
        'to_currency_id' => $eur->id,
        'rate' => 0.85,
        'date' => now(),
    ]);

    $result = $this->converter->convert(100.00, $usd->id, $eur->id);

    expect($result)->toBe(85.00);
});

test('converts using inverse rate when no direct rate exists', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);
    $eur = Currency::factory()->create(['code' => 'EUR', 'name_en' => 'Euro']);

    // Only EUR -> USD rate exists (inverse)
    CurrencyRate::factory()->create([
        'from_currency_id' => $eur->id,
        'to_currency_id' => $usd->id,
        'rate' => 1.25,
        'date' => now(),
    ]);

    // Converting USD -> EUR, should use 1/1.25 = 0.8
    $result = $this->converter->convert(100.00, $usd->id, $eur->id);

    expect($result)->toBe(80.00);
});

test('returns original amount when same currency IDs', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);

    $result = $this->converter->convert(123.45, $usd->id, $usd->id);

    expect($result)->toBe(123.45);
});

test('returns original amount when from currency is null', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);

    $result = $this->converter->convert(99.99, null, $usd->id);

    expect($result)->toBe(99.99);
});

test('returns original amount when to currency is null', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);

    $result = $this->converter->convert(99.99, $usd->id, null);

    expect($result)->toBe(99.99);
});

test('returns original amount and logs warning when no rate found', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'name_en' => 'US Dollar']);
    $eur = Currency::factory()->create(['code' => 'EUR', 'name_en' => 'Euro']);
    // No CurrencyRate created

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, (string) $usd->id)
            && str_contains($message, (string) $eur->id));

    $result = $this->converter->convert(100.00, $usd->id, $eur->id);

    expect($result)->toBe(100.00);
});
