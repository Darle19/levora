<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourAmadeusSegment;
use App\Services\Amadeus\AmadeusFlightService;
use Illuminate\Http\JsonResponse;

class AmadeusSegmentSearchController extends Controller
{
    public function __construct(
        private readonly AmadeusFlightService $flightService,
    ) {}

    public function search(Tour $tour, TourAmadeusSegment $segment): JsonResponse
    {
        if ($segment->tour_id !== $tour->id || ! $segment->is_active) {
            return response()->json(['error' => 'Invalid segment'], 404);
        }

        $segment->load(['originAirport', 'destinationAirport']);

        $originCode = $segment->originAirport->code;
        $destinationCode = $segment->destinationAirport->code;
        $departureDate = $tour->date_from->format('Y-m-d');

        $flights = $this->flightService->searchFlights(
            origin: $originCode,
            destination: $destinationCode,
            departureDate: $departureDate,
            adults: $tour->adults ?? 1,
            children: $tour->children ?? 0,
        );

        return response()->json([
            'segment_id' => $segment->id,
            'origin' => $originCode,
            'destination' => $destinationCode,
            'departure_date' => $departureDate,
            'flights' => $flights,
        ]);
    }
}
