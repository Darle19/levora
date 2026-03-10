<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\TourSearchRequest;
use App\Models\Banner;
use App\Models\Tour;
use App\Services\TourSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TourSearchController extends Controller
{
    public function __construct(
        private TourSearchService $searchService,
    ) {}

    /**
     * Display tour search page.
     */
    public function index(): View
    {
        $filters = $this->searchService->getFilterOptions();
        $banners = Banner::active()->ordered()->get();

        return view('search.tours.index', array_merge($filters, ['banners' => $banners]));
    }

    /**
     * Search for tours (POST form submission).
     */
    public function search(TourSearchRequest $request): View|JsonResponse
    {
        $filters = $request->validated();
        $sortBy = $request->input('sort_by', 'price');
        $sortDir = $request->input('sort_dir', 'asc');
        $groupByHotel = $request->boolean('group_by_hotel');

        $tours = $this->searchService->search($filters, $sortBy, $sortDir);

        $filterOptions = $this->searchService->getFilterOptions();

        $data = array_merge($filterOptions, [
            'tours' => $tours,
            'groupByHotel' => $groupByHotel,
            'currentFilters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'resultsHtml' => view('search.tours._results_table', $data)->render(),
                'paginationHtml' => view('search.tours._pagination', ['tours' => $tours])->render(),
                'total' => $tours->total(),
            ]);
        }

        return view('search.tours.results', $data);
    }

    /**
     * GET results route for bookmarkable searches.
     */
    public function results(TourSearchRequest $request): View|JsonResponse
    {
        return $this->search($request);
    }

    /**
     * Display tour details.
     */
    public function show(Tour $tour): View
    {
        $tour->load([
            'country', 'resort', 'hotel', 'hotel.category', 'hotel.currency',
            'tourType', 'programType', 'transportType',
            'departureCity', 'currency', 'mealType',
            'tourPrices.roomType', 'tourPrices.currency',
            'flights.airline', 'flights.fromAirport', 'flights.toAirport', 'flights.currency',
        ]);

        return view('search.tours.show', compact('tour'));
    }
}
