<?php

namespace App\Observers;

use App\Models\Flight;

class FlightObserver
{
    public function updated(Flight $flight): void
    {
        // FlightPath prices are dynamic (calculated on read) — no recalculation needed
    }
}
