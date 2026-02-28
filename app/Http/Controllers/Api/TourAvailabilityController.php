<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Resort;
use App\Services\TourSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TourAvailabilityController extends Controller
{
    public function __construct(
        private TourSearchService $searchService,
    ) {}

    /**
     * Get available departure dates for a country + departure city combination.
     */
    public function availableDates(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'nullable|integer',
            'departure_city_id' => 'nullable|integer',
        ]);

        $dates = $this->searchService->getAvailableDates(
            $request->integer('country_id') ?: null,
            $request->integer('departure_city_id') ?: null,
        );

        return response()->json(['dates' => $dates]);
    }

    /**
     * Get min/max nights range for given filters.
     */
    public function nightsRange(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
        ]);

        $range = $this->searchService->getNightsRange(
            $request->integer('country_id') ?: null,
            $request->input('date_from'),
        );

        return response()->json($range);
    }

    /**
     * Get resorts for a country with hotel counts.
     */
    public function resorts(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'required|integer',
        ]);

        $countryId = $request->integer('country_id');

        $resorts = Resort::where('is_active', true)
            ->where('country_id', $countryId)
            ->withCount('hotels')
            ->orderBy('order')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'hotels_count' => $r->hotels_count,
            ]);

        return response()->json(['resorts' => $resorts]);
    }

    /**
     * Get hotels filtered by resort IDs.
     */
    public function hotels(Request $request): JsonResponse
    {
        $request->validate([
            'resort_ids' => 'required|array|max:100',
            'resort_ids.*' => 'integer',
        ]);

        $resortIds = $request->input('resort_ids', []);

        $hotels = Hotel::where('is_active', true)
            ->whereIn('resort_id', $resortIds)
            ->with('category')
            ->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'name' => $h->name,
                'resort_id' => $h->resort_id,
                'stars' => $h->category?->stars ?? 0,
            ]);

        return response()->json(['hotels' => $hotels]);
    }
}
