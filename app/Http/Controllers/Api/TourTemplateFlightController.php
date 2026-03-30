<?php

// File: app/Http/Controllers/Api/TourTemplateFlightController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SelectFlightRequest;
use App\Models\TourTemplate;
use App\Models\TourTemplateLeg;
use App\Services\TourTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TourTemplateFlightController extends Controller
{
    public function __construct(
        private readonly TourTemplateService $service,
    ) {}

    /**
     * GET /api/tour-templates/{tourTemplate}/legs/{leg}/flights
     *
     * Search available flights for a specific leg.
     */
    public function search(Request $request, TourTemplate $tourTemplate, TourTemplateLeg $leg): JsonResponse
    {
        abort_if($leg->tour_template_id !== $tourTemplate->id, 404);

        $sortBy = $request->query('sort', 'price');
        $offers = $this->service->searchFlightsForLeg($leg, $sortBy);

        return response()->json([
            'leg' => [
                'id' => $leg->id,
                'order' => $leg->leg_order,
                'from' => $leg->departureCity->name_en,
                'to' => $leg->arrivalCity->name_en,
                'date' => $leg->departure_date->format('Y-m-d'),
            ],
            'offers' => array_map(fn ($o) => $o->toArray(), $offers),
            'count' => count($offers),
        ]);
    }

    /**
     * POST /api/tour-templates/{tourTemplate}/legs/{leg}/flights/select
     *
     * Select a flight offer for this leg.
     */
    public function select(SelectFlightRequest $request, TourTemplate $tourTemplate, TourTemplateLeg $leg): JsonResponse
    {
        abort_if($leg->tour_template_id !== $tourTemplate->id, 404);

        $offerId = $request->validated('offer_id');
        $offers = $this->service->searchFlightsForLeg($leg);

        $matched = null;
        foreach ($offers as $offer) {
            if ($offer->id === $offerId) {
                $matched = $offer;
                break;
            }
        }

        if (! $matched) {
            return response()->json(['error' => 'Offer not found or expired.'], 422);
        }

        $selection = $this->service->selectFlight($leg, $matched);

        return response()->json([
            'selection' => $selection,
            'template_status' => $tourTemplate->fresh()->status->value,
        ], 201);
    }

    /**
     * GET /api/tour-templates/{tourTemplate}/flights
     *
     * List all selected flights for this template.
     */
    public function selections(TourTemplate $tourTemplate): JsonResponse
    {
        $template = $tourTemplate->load('legs.flightSelection', 'legs.departureCity', 'legs.arrivalCity');

        $selections = $template->legs->map(function (TourTemplateLeg $leg) {
            return [
                'leg_order' => $leg->leg_order,
                'from' => $leg->departureCity->name_en,
                'to' => $leg->arrivalCity->name_en,
                'date' => $leg->departure_date->format('Y-m-d'),
                'selected_flight' => $leg->flightSelection ? [
                    'airline' => $leg->flightSelection->airline_code,
                    'flight_number' => $leg->flightSelection->flight_number,
                    'departure' => $leg->flightSelection->departure_datetime->format('Y-m-d H:i'),
                    'arrival' => $leg->flightSelection->arrival_datetime->format('Y-m-d H:i'),
                    'price_cents' => $leg->flightSelection->price_cents,
                    'currency' => $leg->flightSelection->currency,
                ] : null,
            ];
        });

        return response()->json([
            'template_id' => $tourTemplate->id,
            'status' => $tourTemplate->status->value,
            'total_flight_cost_cents' => $tourTemplate->totalFlightCostCents(),
            'all_flights_selected' => $tourTemplate->allFlightsSelected(),
            'legs' => $selections,
        ]);
    }
}
