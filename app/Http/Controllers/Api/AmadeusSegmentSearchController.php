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

    /**
     * Search flights for a single Amadeus segment (one-way).
     */
    public function search(Tour $tour, TourAmadeusSegment $segment): JsonResponse
    {
        if ($segment->tour_id !== $tour->id || ! $segment->is_active) {
            return response()->json(['error' => 'Invalid segment'], 404);
        }

        $segment->load(['originAirport', 'destinationAirport']);

        $originCode = $segment->originAirport->code;
        $destinationCode = $segment->destinationAirport->code;
        $departureDate = $tour->date_from->copy()->addDays($segment->offset_days)->format('Y-m-d');

        $flights = $this->flightService->searchFlights(
            origin: $originCode,
            destination: $destinationCode,
            departureDate: $departureDate,
            adults: $tour->adults ?? 1,
            children: $tour->children ?? 0,
        );

        return response()->json([
            'segment_id' => $segment->id,
            'leg_order' => $segment->leg_order,
            'origin' => $originCode,
            'destination' => $destinationCode,
            'departure_date' => $departureDate,
            'offset_days' => $segment->offset_days,
            'flights' => $flights,
        ]);
    }

    /**
     * Search round-trip flights for all Amadeus segments on a tour.
     * Pairs outbound+return segments that share the same airports (A→B + B→A).
     */
    public function searchRoundTrip(Tour $tour): JsonResponse
    {
        $segments = $tour->amadeusSegments()
            ->where('is_active', true)
            ->with(['originAirport', 'destinationAirport'])
            ->orderBy('leg_order')
            ->get();

        if ($segments->isEmpty()) {
            return response()->json(['error' => 'No Amadeus segments'], 404);
        }

        // Try to pair segments: find A→B and B→A
        $results = [];
        $paired = [];

        foreach ($segments as $outbound) {
            if (in_array($outbound->id, $paired)) {
                continue;
            }

            $returnSegment = $segments->first(function ($s) use ($outbound, $paired) {
                return ! in_array($s->id, $paired)
                    && $s->id !== $outbound->id
                    && $s->origin_airport_id === $outbound->destination_airport_id
                    && $s->destination_airport_id === $outbound->origin_airport_id;
            });

            $originCode = $outbound->originAirport->code;
            $destinationCode = $outbound->destinationAirport->code;
            $departureDate = $tour->date_from->copy()->addDays($outbound->offset_days)->format('Y-m-d');

            if ($returnSegment) {
                // Round-trip search
                $returnDate = $tour->date_from->copy()->addDays($returnSegment->offset_days)->format('Y-m-d');
                $paired[] = $outbound->id;
                $paired[] = $returnSegment->id;

                $flights = $this->flightService->searchFlights(
                    origin: $originCode,
                    destination: $destinationCode,
                    departureDate: $departureDate,
                    returnDate: $returnDate,
                    adults: $tour->adults ?? 1,
                    children: $tour->children ?? 0,
                );

                $results[] = [
                    'type' => 'round_trip',
                    'outbound_segment_id' => $outbound->id,
                    'return_segment_id' => $returnSegment->id,
                    'origin' => $originCode,
                    'destination' => $destinationCode,
                    'departure_date' => $departureDate,
                    'return_date' => $returnDate,
                    'flights' => $flights,
                ];
            } else {
                // Unpaired one-way
                $paired[] = $outbound->id;

                $flights = $this->flightService->searchFlights(
                    origin: $originCode,
                    destination: $destinationCode,
                    departureDate: $departureDate,
                    adults: $tour->adults ?? 1,
                    children: $tour->children ?? 0,
                );

                $results[] = [
                    'type' => 'one_way',
                    'segment_id' => $outbound->id,
                    'origin' => $originCode,
                    'destination' => $destinationCode,
                    'departure_date' => $departureDate,
                    'flights' => $flights,
                ];
            }
        }

        return response()->json([
            'tour_id' => $tour->id,
            'segments' => $results,
        ]);
    }
}
