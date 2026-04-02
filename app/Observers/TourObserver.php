<?php

namespace App\Observers;

use App\Models\Tour;

class TourObserver
{
    public function updated(Tour $tour): void
    {
        // Legacy Tour model — prices are no longer recalculated
    }
}
