<?php

use App\Models\Agency;
use App\Models\Currency;
use App\Models\Flight;
use App\Models\Tour;
use App\Models\User;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $this->agency = Agency::factory()->create(['is_active' => true]);
    $this->user = User::factory()->create([
        'agency_id' => $this->agency->id,
        'is_active' => true,
    ]);
    $this->tour = Tour::factory()->create([
        'currency_id' => $this->currency->id,
        'price' => 1500.00,
        'is_available' => true,
        'date_from' => now()->addWeeks(2),
        'date_to' => now()->addWeeks(2)->addDays(7),
        'adults' => 4,
        'children' => 2,
    ]);
});

function flightTouristData(int $count = 1): array
{
    $tourists = [];
    for ($i = 0; $i < $count; $i++) {
        $tourists[] = [
            'title' => 'MR',
            'gender' => 'male',
            'first_name' => 'Test' . $i,
            'last_name' => 'User' . $i,
            'birth_date' => '1990-01-15',
            'nationality' => 'UZ',
            'passport_number' => 'CD' . str_pad($i, 7, '0', STR_PAD_LEFT),
            'passport_expiry' => now()->addYears(3)->format('Y-m-d'),
        ];
    }
    return $tourists;
}

function flightBookingData(Tour $tour, int $touristCount = 1): array
{
    return [
        'tour_id' => $tour->id,
        'contact_name' => 'Test User',
        'contact_email' => 'test@example.com',
        'contact_phone' => '+998901234567',
        'tourists' => flightTouristData($touristCount),
    ];
}

test('rejects booking when flight has zero available seats', function () {
    $flight = Flight::factory()->create([
        'currency_id' => $this->currency->id,
        'available_seats' => 0,
    ]);
    $this->tour->flights()->attach($flight->id, ['direction' => 'outbound']);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), flightBookingData($this->tour));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('bookings', [
        'bookable_id' => $this->tour->id,
    ]);
});

test('rejects booking when flight has fewer seats than passengers', function () {
    $flight = Flight::factory()->create([
        'currency_id' => $this->currency->id,
        'available_seats' => 1,
    ]);
    $this->tour->flights()->attach($flight->id, ['direction' => 'outbound']);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), flightBookingData($this->tour, 2));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('bookings', [
        'bookable_id' => $this->tour->id,
    ]);
});

test('allows booking and decrements seats when flight has enough seats', function () {
    $flight = Flight::factory()->create([
        'currency_id' => $this->currency->id,
        'available_seats' => 5,
    ]);
    $this->tour->flights()->attach($flight->id, ['direction' => 'outbound']);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), flightBookingData($this->tour, 2));

    $response->assertRedirect();
    $response->assertSessionMissing('error');

    $this->assertDatabaseHas('bookings', [
        'bookable_type' => Tour::class,
        'bookable_id' => $this->tour->id,
        'status' => 'pending',
    ]);

    $flight->refresh();
    expect($flight->available_seats)->toBe(3);
});

test('rejects booking when one of multiple flights is sold out', function () {
    $outboundFlight = Flight::factory()->create([
        'currency_id' => $this->currency->id,
        'available_seats' => 20,
    ]);
    $returnFlight = Flight::factory()->create([
        'currency_id' => $this->currency->id,
        'available_seats' => 0,
    ]);

    $this->tour->flights()->attach($outboundFlight->id, ['direction' => 'outbound']);
    $this->tour->flights()->attach($returnFlight->id, ['direction' => 'return']);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), flightBookingData($this->tour));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('bookings', [
        'bookable_id' => $this->tour->id,
    ]);
});

test('booking form returns 404 when flight has zero available seats', function () {
    $flight = Flight::factory()->create([
        'currency_id' => $this->currency->id,
        'available_seats' => 0,
    ]);
    $this->tour->flights()->attach($flight->id, ['direction' => 'outbound']);

    $response = $this->actingAs($this->user)
        ->get(route('bookings.create', $this->tour));

    $response->assertStatus(404);
});
