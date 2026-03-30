<?php

use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityFlightController;
use App\Http\Controllers\Api\CityHotelController;
use App\Http\Controllers\Api\CityServiceController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\TourAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {

    // Legacy tour availability endpoints
    Route::prefix('tours')->group(function () {
        Route::get('/available-dates', [TourAvailabilityController::class, 'availableDates']);
        Route::get('/nights-range', [TourAvailabilityController::class, 'nightsRange']);
    });
    Route::get('/resorts', [TourAvailabilityController::class, 'resorts']);
    Route::get('/hotels', [TourAvailabilityController::class, 'hotels']);

    // Cities — root entity
    Route::apiResource('cities', CityController::class);

    // City-nested: hotels
    Route::apiResource('cities.hotels', CityHotelController::class)
        ->parameters(['hotels' => 'hotel']);

    // City-nested: additional services
    Route::apiResource('cities.services', CityServiceController::class)
        ->parameters(['services' => 'service']);

    // City-nested: flights (index only — shows flights to/from city)
    Route::get('cities/{city}/flights', [CityFlightController::class, 'index'])
        ->name('cities.flights.index');

    // Flights — standalone CRUD (connects two cities)
    Route::apiResource('flights', FlightController::class);

    // Banners — with optional ?city_id filter
    Route::apiResource('banners', BannerController::class);
});
