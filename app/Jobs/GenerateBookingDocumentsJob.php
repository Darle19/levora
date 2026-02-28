<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\DocumentGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateBookingDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Order $order,
    ) {}

    public function handle(DocumentGenerationService $service): void
    {
        $service->generateAllForOrder($this->order);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Document generation failed for order', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
