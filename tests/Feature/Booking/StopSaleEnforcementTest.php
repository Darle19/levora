<?php

use App\Models\Agency;
use App\Models\Currency;
use App\Models\Hotel;
use App\Models\StopSale;
use App\Models\Tour;
use App\Models\User;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $this->agency = Agency::factory()->create(['is_active' => true]);
    $this->user = User::factory()->create([
        'agency_id' => $this->agency->id,
        'is_active' => true,
    ]);
    $this->hotel = Hotel::factory()->create();
    $this->tour = Tour::factory()->create([
        'currency_id' => $this->currency->id,
        'hotel_id' => $this->hotel->id,
        'price' => 1500.00,
        'is_available' => true,
        'date_from' => now()->addWeeks(2),
        'date_to' => now()->addWeeks(2)->addDays(7),
    ]);
});

test('blocks booking when active stop sale covers today', function () {
    StopSale::factory()->create([
        'hotel_id' => $this->hotel->id,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(30),
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), [
            'tour_id' => $this->tour->id,
            'contact_name' => 'Test User',
            'contact_email' => 'test@example.com',
            'contact_phone' => '+998901234567',
            'tourists' => [[
                'title' => 'MR',
                'gender' => 'male',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '1990-01-15',
                'nationality' => 'UZ',
                'passport_number' => 'AB1234567',
                'passport_expiry' => now()->addYears(3)->format('Y-m-d'),
            ]],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('allows booking when stop sale is inactive', function () {
    StopSale::factory()->create([
        'hotel_id' => $this->hotel->id,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(30),
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), [
            'tour_id' => $this->tour->id,
            'contact_name' => 'Test User',
            'contact_email' => 'test@example.com',
            'contact_phone' => '+998901234567',
            'tourists' => [[
                'title' => 'MR',
                'gender' => 'male',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '1990-01-15',
                'nationality' => 'UZ',
                'passport_number' => 'AB1234567',
                'passport_expiry' => now()->addYears(3)->format('Y-m-d'),
            ]],
        ]);

    $response->assertRedirect();
    $response->assertSessionMissing('error');
});

test('allows booking when stop sale has expired', function () {
    StopSale::factory()->create([
        'hotel_id' => $this->hotel->id,
        'start_date' => now()->subDays(30),
        'end_date' => now()->subDays(1),
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), [
            'tour_id' => $this->tour->id,
            'contact_name' => 'Test User',
            'contact_email' => 'test@example.com',
            'contact_phone' => '+998901234567',
            'tourists' => [[
                'title' => 'MR',
                'gender' => 'male',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '1990-01-15',
                'nationality' => 'UZ',
                'passport_number' => 'AB1234567',
                'passport_expiry' => now()->addYears(3)->format('Y-m-d'),
            ]],
        ]);

    $response->assertRedirect();
    $response->assertSessionMissing('error');
});
