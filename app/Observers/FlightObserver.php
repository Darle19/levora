<?php

namespace App\Observers;

use App\Models\Flight;
use App\Services\TourPricingService;

class FlightObserver
{
    public function updated(Flight $flight): void
    {
        if ($flight->wasChanged(['price_adult', 'price_child', 'price_infant', 'currency_id'])) {
            app(TourPricingService::class)->recalculateForFlight($flight->id);
        }
    }
}
