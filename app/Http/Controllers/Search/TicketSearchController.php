<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Services\Amadeus\AmadeusFlightService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketSearchController extends Controller
{
    public function __construct(
        private AmadeusFlightService $flightService,
    ) {}

    /**
     * Display ticket search page.
     */
    public function index(): View
    {
        $airports = Airport::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('search.tickets.index', compact('airports'));
    }

    /**
     * Search for flight tickets.
     */
    public function search(Request $request): View
    {
        $validated = $request->validate([
            'origin' => 'required|string|size:3',
            'destination' => 'required|string|size:3',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'adults' => 'required|integer|min:1|max:9',
            'children' => 'nullable|integer|min:0|max:8',
            'infants' => 'nullable|integer|min:0|max:4',
            'travel_class' => 'nullable|in:ECONOMY,PREMIUM_ECONOMY,BUSINESS,FIRST',
            'non_stop' => 'nullable|boolean',
        ]);

        $airports = Airport::where('is_active', true)->orderBy('name')->get();

        $flights = $this->flightService->searchFlights(
            origin: $validated['origin'],
            destination: $validated['destination'],
            departureDate: $validated['departure_date'],
            returnDate: $validated['return_date'] ?? null,
            adults: $validated['adults'],
            children: $validated['children'] ?? 0,
            infants: $validated['infants'] ?? 0,
            travelClass: $validated['travel_class'] ?? 'ECONOMY',
            nonStop: $request->boolean('non_stop'),
        );

        return view('search.tickets.results', compact('flights', 'airports', 'validated'));
    }
}
