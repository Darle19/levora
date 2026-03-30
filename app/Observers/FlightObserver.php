<?php

namespace App\Observers;

use App\Models\Flight;
use App\Models\FlightPath;
use App\Services\TourPricingService;

class FlightObserver
{
    public function updated(Flight $flight): void
    {
        if ($flight->wasChanged(['price_adult', 'price_child', 'price_infant', 'currency_id'])) {
            // Recalculate old Tour prices
            app(TourPricingService::class)->recalculateForFlight($flight->id);

            // Recalculate FlightPath total prices
            $flightPathIds = $flight->flightPathLegs()->pluck('flight_path_id')->unique();
            foreach ($flightPathIds as $fpId) {
                $fp = FlightPath::find($fpId);
                if ($fp) {
                    $fp->recalculatePrice();
                }
            }
        }
    }
}
