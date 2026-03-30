<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Flight;
use Illuminate\Http\JsonResponse;

class CityFlightController extends Controller
{
    /**
     * Flights where this city is either origin OR destination.
     */
    public function index(City $city): JsonResponse
    {
        $flights = Flight::forCity($city->id)
            ->with('airline', 'originCity', 'destinationCity', 'fromAirport', 'toAirport', 'currency')
            ->orderBy('departure_date')
            ->get();

        return response()->json($flights);
    }
}
