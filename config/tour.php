<?php

// File: config/tour.php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('TOUR_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default Margin Percentage
    |--------------------------------------------------------------------------
    */
    'default_margin_percent' => env('TOUR_DEFAULT_MARGIN', 10),

    /*
    |--------------------------------------------------------------------------
    | Flight Stale Threshold (hours)
    |--------------------------------------------------------------------------
    | Local DB flight data older than this will trigger an external provider
    | lookup as fallback.
    |
    */
    'flight_stale_hours' => env('TOUR_FLIGHT_STALE_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Flight Provider
    |--------------------------------------------------------------------------
    | The default flight provider implementation class.
    | Swap this out for Amadeus, Duffel, etc.
    |
    */
    'flight_provider' => env('TOUR_FLIGHT_PROVIDER', \App\Services\Flights\DummyFlightProvider::class),

];
