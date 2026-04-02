<?php

namespace App\Observers;

use App\Models\Hotel;

class HotelObserver
{
    public function updated(Hotel $hotel): void
    {
        // Prices are dynamic (calculated on read) — no recalculation needed
    }
}
