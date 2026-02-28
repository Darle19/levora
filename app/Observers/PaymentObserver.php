<?php

namespace App\Observers;

use App\Jobs\GenerateBookingDocumentsJob;
use App\Models\Payment;

class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        if ($payment->status !== 'completed') {
            return;
        }

        $order = $payment->order;
        $percentage = $order->getPaymentPercentage();

        // Auto-generate documents at 30% (queued)
        if ($percentage >= 30 && !$order->hasDocuments()) {
            GenerateBookingDocumentsJob::dispatch($order);
        }

        // Auto-update order status based on payment
        if ($percentage >= 100 && $order->status !== 'paid') {
            $order->updateQuietly(['status' => 'paid']);
        } elseif ($percentage >= 30 && $order->status === 'pending') {
            $order->updateQuietly(['status' => 'confirmed']);
        }
    }
}
