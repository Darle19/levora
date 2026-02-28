<?php

use App\Jobs\GenerateBookingDocumentsJob;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tour;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $this->order = Order::factory()->create([
        'currency_id' => $this->currency->id,
        'total_price' => 1000.00,
        'status' => 'pending',
    ]);
    $this->booking = Booking::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'price' => 1000.00,
    ]);
});

test('dispatches document generation job at 30% payment', function () {
    Queue::fake();

    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 300.00,
        'status' => 'completed',
    ]);

    Queue::assertPushed(GenerateBookingDocumentsJob::class, function ($job) {
        return $job->order->id === $this->order->id;
    });
});

test('does not dispatch documents for pending payment', function () {
    Queue::fake();

    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 300.00,
        'status' => 'pending',
    ]);

    Queue::assertNotPushed(GenerateBookingDocumentsJob::class);
});

test('updates order status to confirmed at 30%', function () {
    Queue::fake();

    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 300.00,
        'status' => 'completed',
    ]);

    $this->order->refresh();
    expect($this->order->status)->toBe('confirmed');
});

test('updates order status to paid at 100%', function () {
    Queue::fake();

    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 1000.00,
        'status' => 'completed',
    ]);

    $this->order->refresh();
    expect($this->order->status)->toBe('paid');
});
