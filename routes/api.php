<?php

use App\Http\Controllers\Api\TourAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::prefix('tours')->group(function () {
        Route::get('/available-dates', [TourAvailabilityController::class, 'availableDates']);
        Route::get('/nights-range', [TourAvailabilityController::class, 'nightsRange']);
    });

    Route::get('/resorts', [TourAvailabilityController::class, 'resorts']);
    Route::get('/hotels', [TourAvailabilityController::class, 'hotels']);
});
