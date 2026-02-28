<?php

namespace App\Observers;

use App\Models\Hotel;
use App\Services\TourPricingService;

class HotelObserver
{
    public function updated(Hotel $hotel): void
    {
        if ($hotel->wasChanged(['price_per_person', 'currency_id'])) {
            app(TourPricingService::class)->recalculateForHotel($hotel->id);
        }
    }
}
