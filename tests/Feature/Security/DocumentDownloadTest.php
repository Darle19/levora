<?php

use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingDocument;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $this->agency = Agency::factory()->create(['is_active' => true]);
    $this->user = User::factory()->create([
        'agency_id' => $this->agency->id,
        'is_active' => true,
    ]);
    $this->tour = Tour::factory()->create([
        'currency_id' => $this->currency->id,
        'price' => 1000.00,
    ]);
    $this->order = Order::factory()->create([
        'agency_id' => $this->agency->id,
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'total_price' => 1000.00,
    ]);
    $this->booking = Booking::factory()->create([
        'order_id' => $this->order->id,
        'bookable_type' => Tour::class,
        'bookable_id' => $this->tour->id,
        'currency_id' => $this->currency->id,
        'price' => 1000.00,
    ]);
});

test('denies document download for different agency', function () {
    $otherAgency = Agency::factory()->create(['is_active' => true]);
    $otherUser = User::factory()->create([
        'agency_id' => $otherAgency->id,
        'is_active' => true,
    ]);

    $document = BookingDocument::factory()->confirmation()->create([
        'booking_id' => $this->booking->id,
        'file_path' => "documents/{$this->order->id}/confirmation_{$this->booking->id}.pdf",
    ]);

    $response = $this->actingAs($otherUser)
        ->get(route('documents.download', $document));

    $response->assertStatus(403);
});

test('denies document download when order not fully paid', function () {
    // Pay only 50%
    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 500.00,
        'status' => 'completed',
    ]);

    $document = BookingDocument::factory()->confirmation()->create([
        'booking_id' => $this->booking->id,
        'file_path' => "documents/{$this->order->id}/confirmation_{$this->booking->id}.pdf",
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('documents.download', $document));

    $response->assertStatus(403);
});

test('blocks path traversal in document file_path', function () {
    // Fully pay the order
    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 1000.00,
        'status' => 'completed',
    ]);

    // Create document with tampered file_path pointing to wrong directory
    $document = BookingDocument::factory()->confirmation()->create([
        'booking_id' => $this->booking->id,
        'file_path' => 'documents/999/../../../etc/passwd',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('documents.download', $document));

    $response->assertStatus(403);
});

test('allows document download when fully paid and authorized', function () {
    // Fully pay the order
    Payment::factory()->create([
        'order_id' => $this->order->id,
        'currency_id' => $this->currency->id,
        'amount' => 1000.00,
        'status' => 'completed',
    ]);

    $filePath = "documents/{$this->order->id}/confirmation_{$this->booking->id}.pdf";

    Storage::disk('local')->put($filePath, 'fake pdf content');

    $document = BookingDocument::factory()->confirmation()->create([
        'booking_id' => $this->booking->id,
        'file_path' => $filePath,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('documents.download', $document));

    $response->assertStatus(200);

    Storage::disk('local')->delete($filePath);
});
