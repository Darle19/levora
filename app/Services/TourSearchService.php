<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FlightPath;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\MealType;
use App\Models\Setting;
use App\Services\TourPriceCalculator;
use Illuminate\Support\Facades\Cache;

/**
 * Search service for the new architecture:
 * FlightPath × Hotels = dynamic tour combos (not stored in DB).
 *
 * Each "result" = 1 flight_path + 1 hotel per city stay.
 * Price = flight_path.total_price + SUM(hotel.price/2 × nights) + fees
 */
class TourSearchService
{
    /** In-memory memo so buildRoutes() isn't rebuilt twice per request. */
    private ?array $routesMemo = null;

    public function getFilterOptions(): array
    {
        return Cache::remember('tour_filter_options', 1800, fn () => [
            'countries' => Country::where('is_active', true)->orderBy('order')->get(),
            'cities' => City::where('is_active', true)->orderBy('order')->get(),
            'departureCities' => City::where('is_active', true)->where('is_departure', true)->orderBy('name_en')->get(),
            'tourRoutes' => $this->getRoutes(),
            'mealTypes' => MealType::where('is_active', true)->get(),
            'hotelCategories' => HotelCategory::where('is_active', true)->orderBy('stars')->get(),
            'currencies' => Currency::where('is_active', true)->get(),
            'hotelsByCity' => Hotel::where('is_active', true)
                ->whereNotNull('city_id')
                ->with('category')
                ->get()
                ->groupBy('city_id'),
            'departureDates' => FlightPath::where('is_available', true)
                ->where('departure_date', '>=', now()->toDateString())
                ->distinct()
                ->pluck('departure_date')
                ->map(fn ($d) => $d->format('Y-m-d'))
                ->values()
                ->toArray(),
            'dateSeats' => $this->buildDateSeats(),
        ]);
    }

    /** Return cached (per-request) route data, building on first access. */
    private function getRoutes(): array
    {
        return $this->routesMemo ??= $this->buildRoutes();
    }

    /**
     * Build route options from flight_paths.
     */
    private function buildRoutes(): array
    {
        $paths = FlightPath::where('is_available', true)
            ->with(['stays.city', 'legs.flight:id,available_seats'])
            ->get();

        // Batch-load all active hotels grouped by city_id (avoids N Hotel queries in the route loop).
        $hotelIdsByCity = Hotel::where('is_active', true)
            ->whereNotNull('city_id')
            ->get(['id', 'city_id'])
            ->groupBy('city_id')
            ->map->pluck('id');

        $grouped = $paths->groupBy('route_name');
        $routes = [];

        foreach ($grouped as $routeName => $pathGroup) {
            $slug = str($routeName)->slug()->toString();
            $firstPath = $pathGroup->first();
            $cityIds = $firstPath->stays->pluck('city_id')->filter()->values()->toArray();
            $departureCityId = $firstPath->departure_city_id;

            // All countries visited in this route (tour may span multiple countries)
            $countryIds = $firstPath->stays->pluck('city.country_id')->filter()->unique()->values()->toArray();
            // Primary country (used for auto-filling country dropdown when route is picked) = last stay
            $countryId = $firstPath->stays->sortByDesc('stay_order')->first()?->city?->country_id;

            $dateFrom = $pathGroup->min('departure_date')?->format('Y-m-d');
            $dateTo = $pathGroup->max('departure_date')?->format('Y-m-d');
            $nights = $firstPath->nights;

            // Build date → min seats for this route
            $routeDateSeats = [];
            foreach ($pathGroup as $fp) {
                $date = $fp->departure_date->format('Y-m-d');
                $minSeats = $fp->legs->min(fn ($leg) => $leg->flight->available_seats ?? 0);
                $routeDateSeats[$date] = $minSeats;
            }

            $hotelIds = collect($cityIds)
                ->flatMap(fn ($cid) => $hotelIdsByCity[$cid] ?? collect())
                ->values()
                ->all();

            $routes[] = [
                'slug' => $slug,
                'label' => $routeName,
                'filters' => [
                    'country_id' => $countryId,
                    'country_ids' => $countryIds,
                    'departure_city_id' => $departureCityId,
                    'city_ids' => $cityIds,
                    'hotel_ids' => $hotelIds,
                    'nights_from' => $nights,
                    'nights_to' => $nights,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'date_seats' => $routeDateSeats,
                ],
            ];
        }

        return $routes;
    }

    /**
     * Search: returns flight_path × hotel combinations.
     * Each result is a stdClass with: flight_path, hotels (per stay), price.
     */
    public function search(array $filters, string $sortBy = 'price', string $sortDir = 'asc', int $perPage = 20): array
    {
        $query = FlightPath::query()
            ->where('is_available', true)
            ->with(['legs.flight.fromAirport', 'legs.flight.toAirport', 'legs.flight.airline', 'stays.city', 'currency']);

        // Route filter
        if (! empty($filters['tour_route'])) {
            $routeSlug = $filters['tour_route'];
            $matched = collect($this->getRoutes())->firstWhere('slug', $routeSlug);
            if ($matched) {
                $query->where('route_name', $matched['label']);
            }
        }

        // Country filter — match flight paths whose destination (last) city is in the selected country
        if (! empty($filters['country_id'])) {
            $query->whereHas('stays.city', fn ($q) => $q->where('country_id', $filters['country_id']));
        }

        // Date range
        if (! empty($filters['date_from'])) {
            $query->where('departure_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('departure_date', '<=', $filters['date_to']);
        }

        // Nights
        if (! empty($filters['nights_from'])) {
            $query->where('nights', '>=', $filters['nights_from']);
        }
        if (! empty($filters['nights_to'])) {
            $query->where('nights', '<=', $filters['nights_to']);
        }

        $flightPaths = $query->orderBy('departure_date')->get();

        // Get hotels per city
        $hotelFilter = [];
        if (! empty($filters['hotel_ids'])) {
            $hotelFilter = $filters['hotel_ids'];
        }

        $adults = (int) ($filters['adults'] ?? 2);

        // Batch-load all hotels for cities referenced by flight path stays (avoids N+1 in the loop below)
        $stayCityIds = $flightPaths->pluck('stays.*.city_id')->flatten()->filter()->unique()->values()->all();
        $hotelsByCityId = [];
        if (! empty($stayCityIds)) {
            $hotelsByCityId = Hotel::whereIn('city_id', $stayCityIds)
                ->where('is_active', true)
                ->when(! empty($hotelFilter), fn ($q) => $q->whereIn('id', $hotelFilter))
                ->with('category')
                ->get()
                ->groupBy('city_id');
        }

        // Build combos: flight_path × hotels
        $results = [];
        foreach ($flightPaths as $fp) {
            $hotelsByCityStay = [];
            foreach ($fp->stays as $stay) {
                $cityHotels = $hotelsByCityId[$stay->city_id] ?? collect();
                $hotelsByCityStay[$stay->stay_order] = [
                    'city' => $stay->city,
                    'nights' => $stay->nights,
                    'hotels' => $cityHotels,
                ];
            }

            $combos = $this->cartesianHotels($hotelsByCityStay);

            foreach ($combos as $combo) {
                $stayHotels = [];
                foreach ($combo as $stayData) {
                    $stayHotels[] = [
                        'hotel' => $stayData['hotel'],
                        'nights' => $stayData['nights'],
                        'city_id' => $stayData['city']->id ?? null,
                    ];
                }

                $breakdown = TourPriceCalculator::calculate($fp, $stayHotels, $adults);
                $minSeats = $fp->legs->min(fn ($leg) => $leg->flight->available_seats ?? 0);

                $results[] = (object) [
                    'flight_path' => $fp,
                    'hotels' => $combo,
                    'price' => $breakdown['price_per_person'],
                    'flight_price' => $breakdown['flight_total'],
                    'hotel_cost' => $breakdown['hotel_per_person'],
                    'fees' => $breakdown['hidden_fee'] + $breakdown['agent_fee'],
                    'services_cost' => $breakdown['mandatory_services_cost'],
                    'min_seats' => $minSeats,
                    'currency' => $fp->currency,
                ];
            }
        }

        // Sort
        usort($results, function ($a, $b) use ($sortBy, $sortDir) {
            $valA = match ($sortBy) {
                'price' => $a->price,
                'date_from' => $a->flight_path->departure_date->timestamp,
                'nights' => $a->flight_path->nights,
                default => $a->price,
            };
            $valB = match ($sortBy) {
                'price' => $b->price,
                'date_from' => $b->flight_path->departure_date->timestamp,
                'nights' => $b->flight_path->nights,
                default => $b->price,
            };
            return $sortDir === 'asc' ? $valA <=> $valB : $valB <=> $valA;
        });

        // Price filter (after calculation)
        if (! empty($filters['price_from'])) {
            $results = array_filter($results, fn ($r) => $r->price >= (float) $filters['price_from']);
        }
        if (! empty($filters['price_to'])) {
            $results = array_filter($results, fn ($r) => $r->price <= (float) $filters['price_to']);
        }

        return array_values($results);
    }

    /**
     * Cartesian product of hotels across stays.
     * Input: [1 => ['city'=>..., 'nights'=>2, 'hotels'=>[H1,H2]], 2 => ['city'=>..., 'nights'=>4, 'hotels'=>[H3]]]
     * Output: [[1=>['hotel'=>H1,'nights'=>2,'city'=>...], 2=>['hotel'=>H3,'nights'=>4,'city'=>...]], [1=>['hotel'=>H2,...], 2=>['hotel'=>H3,...]], ...]
     */
    private function cartesianHotels(array $hotelsByCityStay): array
    {
        $result = [[]];

        foreach ($hotelsByCityStay as $stayOrder => $data) {
            $newResult = [];
            foreach ($result as $combo) {
                foreach ($data['hotels'] as $hotel) {
                    $newResult[] = $combo + [$stayOrder => [
                        'hotel' => $hotel,
                        'nights' => $data['nights'],
                        'city' => $data['city'],
                    ]];
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    /**
     * Build date → min seats map for calendar coloring.
     * Green (>10), Yellow (1-10), Red (0).
     */
    private function buildDateSeats(): array
    {
        $paths = FlightPath::where('is_available', true)
            ->where('departure_date', '>=', now()->toDateString())
            ->with('legs.flight')
            ->get();

        $dateSeats = [];
        foreach ($paths as $fp) {
            $date = $fp->departure_date->format('Y-m-d');
            $minSeats = $fp->legs->min(fn ($leg) => $leg->flight->available_seats ?? 0);
            // Keep minimum across all paths for the same date
            if (! isset($dateSeats[$date]) || $minSeats < $dateSeats[$date]) {
                $dateSeats[$date] = $minSeats;
            }
        }

        return $dateSeats;
    }
}
