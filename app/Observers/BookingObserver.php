<?php

namespace App\Observers;

use App\Models\Booking;

/**
 * Order and Booking have independent lifecycles and disjoint status sets
 * (orders: pending/confirmed/paid/cancelled; bookings: +completed).
 * Naïvely copying booking.status onto order.status used to blow up the
 * CHECK constraint when admin marked a booking 'completed'.
 *
 * The only transition we still propagate is cancellation: if the booking
 * is cancelled, cancel the parent order too (unless it's already paid —
 * then a refund decision belongs to ops, not an observer).
 */
class BookingObserver
{
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        if ($booking->status === 'cancelled') {
            $order = $booking->order;
            if ($order && $order->status !== 'paid' && $order->status !== 'cancelled') {
                $order->updateQuietly(['status' => 'cancelled']);
            }
        }
    }
}
