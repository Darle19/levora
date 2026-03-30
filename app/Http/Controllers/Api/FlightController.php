<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFlightRequest;
use App\Http\Requests\Api\UpdateFlightRequest;
use App\Models\Flight;
use Illuminate\Http\JsonResponse;

class FlightController extends Controller
{
    public function index(): JsonResponse
    {
        $flights = Flight::with('airline', 'originCity', 'destinationCity', 'fromAirport', 'toAirport', 'currency')
            ->orderBy('departure_date')
            ->get();

        return response()->json($flights);
    }

    public function store(StoreFlightRequest $request): JsonResponse
    {
        $flight = Flight::create($request->validated());

        return response()->json(
            $flight->load('airline', 'originCity', 'destinationCity', 'fromAirport', 'toAirport', 'currency'),
            201
        );
    }

    public function show(Flight $flight): JsonResponse
    {
        return response()->json(
            $flight->load('airline', 'originCity', 'destinationCity', 'fromAirport', 'toAirport', 'currency')
        );
    }

    public function update(UpdateFlightRequest $request, Flight $flight): JsonResponse
    {
        $flight->update($request->validated());

        return response()->json(
            $flight->fresh('airline', 'originCity', 'destinationCity', 'fromAirport', 'toAirport', 'currency')
        );
    }

    public function destroy(Flight $flight): JsonResponse
    {
        $flight->delete();

        return response()->json(null, 204);
    }
}
