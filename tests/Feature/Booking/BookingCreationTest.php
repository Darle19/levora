<?php

use App\Models\Agency;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Order;
use App\Models\StopSale;
use App\Models\Tour;
use App\Models\Tourist;
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
    ]);
});

function touristData(int $count = 1): array
{
    $tourists = [];
    for ($i = 0; $i < $count; $i++) {
        $tourists[] = [
            'title' => 'MR',
            'gender' => 'male',
            'first_name' => 'John' . $i,
            'last_name' => 'Doe' . $i,
            'birth_date' => '1990-01-15',
            'nationality' => 'UZ',
            'passport_number' => 'AB' . str_pad($i, 7, '0', STR_PAD_LEFT),
            'passport_expiry' => now()->addYears(3)->format('Y-m-d'),
        ];
    }
    return $tourists;
}

function validBookingData(Tour $tour, int $touristCount = 1): array
{
    return [
        'tour_id' => $tour->id,
        'contact_name' => 'John Doe',
        'contact_email' => 'john@example.com',
        'contact_phone' => '+998901234567',
        'tourists' => touristData($touristCount),
    ];
}

test('shows booking form for available tour', function () {
    $response = $this->actingAs($this->user)
        ->get(route('bookings.create', $this->tour));

    $response->assertStatus(200);
});

test('returns 404 for unavailable tour', function () {
    $tour = Tour::factory()->create([
        'currency_id' => $this->currency->id,
        'is_available' => false,
        'date_from' => now()->addWeeks(2),
        'date_to' => now()->addWeeks(3),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('bookings.create', $tour));

    $response->assertStatus(404);
});

test('returns 404 for past tour', function () {
    $tour = Tour::factory()->past()->create([
        'currency_id' => $this->currency->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('bookings.create', $tour));

    $response->assertStatus(404);
});

test('creates booking with valid data', function () {
    $data = validBookingData($this->tour);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'agency_id' => $this->agency->id,
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('bookings', [
        'bookable_type' => Tour::class,
        'bookable_id' => $this->tour->id,
        'status' => 'pending',
    ]);
});

test('rejects booking for unavailable tour via POST', function () {
    $tour = Tour::factory()->create([
        'currency_id' => $this->currency->id,
        'is_available' => false,
        'date_from' => now()->addWeeks(2),
        'date_to' => now()->addWeeks(3),
    ]);
    $data = validBookingData($tour);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('rejects booking for tour with active stop sale', function () {
    StopSale::factory()->create([
        'hotel_id' => $this->tour->hotel_id,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(30),
        'is_active' => true,
    ]);

    $data = validBookingData($this->tour);

    $response = $this->actingAs($this->user)
        ->post(route('bookings.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('calculates total price correctly based on tourist count', function () {
    $data = validBookingData($this->tour, 3);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), $data);

    $expectedTotal = $this->tour->price * 3;

    $order = Order::where('user_id', $this->user->id)->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total_price)->toBe((float) $expectedTotal);

    $booking = Booking::where('order_id', $order->id)->first();
    expect((float) $booking->price)->toBe((float) $expectedTotal);
});

test('creates tourist records for each tourist', function () {
    $data = validBookingData($this->tour, 2);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), $data);

    $booking = Booking::first();
    expect($booking)->not->toBeNull();

    $tourists = Tourist::where('booking_id', $booking->id)->get();
    expect($tourists)->toHaveCount(2);
    expect($tourists[0]->first_name)->toBe('John0');
    expect($tourists[1]->first_name)->toBe('John1');
});

test('generates ULID-based order number starting with ORD-', function () {
    $data = validBookingData($this->tour);

    $this->actingAs($this->user)
        ->post(route('bookings.store'), $data);

    $order = Order::where('user_id', $this->user->id)->first();
    expect($order)->not->toBeNull();
    expect($order->order_number)->toStartWith('ORD-');
    expect(strlen($order->order_number))->toBeGreaterThan(4);
});

test('requires authentication to access booking form', function () {
    $response = $this->get(route('bookings.create', $this->tour));

    $response->assertRedirect(route('login'));
});
