<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\TourSearchRequest;
use App\Models\Banner;
use App\Models\FlightPath;
use App\Services\TourSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TourSearchController extends Controller
{
    public function __construct(
        private TourSearchService $searchService,
    ) {}

    public function index(): View
    {
        $filters = $this->searchService->getFilterOptions();
        $banners = Banner::active()->ordered()->get();

        return view('search.tours.index', array_merge($filters, ['banners' => $banners]));
    }

    public function search(TourSearchRequest $request): View|JsonResponse
    {
        $filters = $request->validated();
        $sortBy = $request->input('sort_by', 'price');
        $sortDir = $request->input('sort_dir', 'asc');

        // Store adults in session for booking page
        session(['booking_adults' => (int) ($filters['adults'] ?? 2)]);

        $results = $this->searchService->search($filters, $sortBy, $sortDir);

        $filterOptions = $this->searchService->getFilterOptions();

        $data = array_merge($filterOptions, [
            'results' => $results,
            'currentFilters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'resultsHtml' => view('search.tours._results_table', $data)->render(),
                'paginationHtml' => '',
                'total' => count($results),
            ]);
        }

        return view('search.tours.results', $data);
    }

    public function results(TourSearchRequest $request): View|JsonResponse
    {
        return $this->search($request);
    }

    public function show(FlightPath $flightPath)
    {
        // Tour detail page not yet rebuilt for FlightPath — redirect to search
        return redirect()->route('search.tours');
    }
}
