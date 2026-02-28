<?php

use App\Models\Agency;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Tour;
use App\Models\User;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD']);

    // Owning agency and user
    $this->owningAgency = Agency::factory()->create(['is_active' => true]);
    $this->owningUser = User::factory()->create([
        'agency_id' => $this->owningAgency->id,
        'is_active' => true,
    ]);

    // Other agency and user
    $this->otherAgency = Agency::factory()->create(['is_active' => true]);
    $this->otherUser = User::factory()->create([
        'agency_id' => $this->otherAgency->id,
        'is_active' => true,
    ]);

    // Create tour, order, and booking owned by owningAgency
    $this->tour = Tour::factory()->create(['currency_id' => $this->currency->id]);
    $this->order = Order::factory()->create([
        'agency_id' => $this->owningAgency->id,
        'user_id' => $this->owningUser->id,
        'currency_id' => $this->currency->id,
    ]);
    $this->booking = Booking::factory()->create([
        'order_id' => $this->order->id,
        'bookable_type' => Tour::class,
        'bookable_id' => $this->tour->id,
        'currency_id' => $this->currency->id,
    ]);
});

test('owning agency can view booking confirmation', function () {
    $response = $this->actingAs($this->owningUser)
        ->get(route('bookings.confirmation', $this->booking));

    $response->assertStatus(200);
});

test('other agency gets 403 on booking confirmation', function () {
    $response = $this->actingAs($this->otherUser)
        ->get(route('bookings.confirmation', $this->booking));

    $response->assertStatus(403);
});

test('owning agency can view claim details', function () {
    $response = $this->actingAs($this->owningUser)
        ->get(route('claims.show', $this->order));

    $response->assertStatus(200);
});

test('other agency gets 403 on claim details', function () {
    $response = $this->actingAs($this->otherUser)
        ->get(route('claims.show', $this->order));

    $response->assertStatus(403);
});
