<?php

namespace App\Observers;

use App\Models\Booking;

class BookingObserver
{
    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status')) {
            $booking->order?->updateQuietly(['status' => $booking->status]);
        }
    }
}
