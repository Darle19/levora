<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\DocumentGenerationService;

class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        if ($payment->status !== 'completed') {
            return;
        }

        $order = $payment->order;
        $percentage = $order->getPaymentPercentage();

        // Auto-generate documents at 30%
        if ($percentage >= 30 && !$order->hasDocuments()) {
            app(DocumentGenerationService::class)->generateAllForOrder($order);
        }

        // Auto-update order status based on payment
        if ($percentage >= 100 && $order->status !== 'paid') {
            $order->updateQuietly(['status' => 'paid']);
        } elseif ($percentage >= 30 && $order->status === 'pending') {
            $order->updateQuietly(['status' => 'confirmed']);
        }
    }
}
