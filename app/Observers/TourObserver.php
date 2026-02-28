<?php

namespace App\Observers;

use App\Models\Tour;
use App\Services\TourPricingService;

class TourObserver
{
    public function updated(Tour $tour): void
    {
        if ($tour->wasChanged(['markup_percent', 'hotel_id', 'currency_id'])
            && ! $tour->wasChanged('price')) {
            app(TourPricingService::class)->recalculate($tour);
        }
    }
}
