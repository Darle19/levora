<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Models\MealType;
use App\Models\ProgramType;
use App\Models\Resort;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\TransportType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TourSearchService
{
    public function getFilterOptions(): array
    {
        return Cache::remember('tour_filter_options', 1800, fn() => [
            'countries' => Country::where('is_active', true)->orderBy('order')->get(),
            'cities' => City::where('is_active', true)->orderBy('order')->get(),
            'tourTypes' => TourType::where('is_active', true)->get(),
            'programTypes' => ProgramType::where('is_active', true)->get(),
            'transportTypes' => TransportType::where('is_active', true)->get(),
            'mealTypes' => MealType::where('is_active', true)->get(),
            'hotelCategories' => HotelCategory::where('is_active', true)->orderBy('stars')->get(),
            'currencies' => Currency::where('is_active', true)->get(),
            'resortsByCountry' => Resort::where('is_active', true)
                ->with('country')
                ->orderBy('order')
                ->get()
                ->groupBy('country_id'),
            'hotelsByResort' => Hotel::where('is_active', true)
                ->with(['resort', 'category'])
                ->get()
                ->groupBy('resort_id'),
        ]);
    }

    public function search(array $filters, string $sortBy = 'price', string $sortDir = 'asc', int $perPage = 20): LengthAwarePaginator
    {
        $query = Tour::query()->where('is_available', true);

        // Country filter
        if (! empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        // Multiple resort filter
        if (! empty($filters['resort_ids'])) {
            $query->whereIn('resort_id', $filters['resort_ids']);
        }

        // Multiple hotel filter
        if (! empty($filters['hotel_ids'])) {
            $query->whereIn('hotel_id', $filters['hotel_ids']);
        }

        // Departure city filter
        if (! empty($filters['departure_city_id'])) {
            $query->where('departure_city_id', $filters['departure_city_id']);
        }

        // Tour type filter
        if (! empty($filters['tour_type_id'])) {
            $query->where('tour_type_id', $filters['tour_type_id']);
        }

        // Program type filter
        if (! empty($filters['program_type_id'])) {
            $query->where('program_type_id', $filters['program_type_id']);
        }

        // Transport type filter
        if (! empty($filters['transport_type_id'])) {
            $query->where('transport_type_id', $filters['transport_type_id']);
        }

        // Date filters
        if (! empty($filters['date_from'])) {
            $query->where('date_from', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('date_from', '<=', $filters['date_to']);
        }

        // Nights range
        if (! empty($filters['nights_from'])) {
            $query->where('nights', '>=', $filters['nights_from']);
        }
        if (! empty($filters['nights_to'])) {
            $query->where('nights', '<=', $filters['nights_to']);
        }

        // Travelers
        if (! empty($filters['adults'])) {
            $query->where('adults', '>=', $filters['adults']);
        }
        if (! empty($filters['children'])) {
            $query->where('children', '>=', $filters['children']);
        }

        // Price range
        if (! empty($filters['price_from'])) {
            $query->where('price', '>=', $filters['price_from']);
        }
        if (! empty($filters['price_to'])) {
            $query->where('price', '<=', $filters['price_to']);
        }

        // Multiple meal type
        if (! empty($filters['meal_type_ids'])) {
            $query->whereIn('meal_type_id', $filters['meal_type_ids']);
        }

        // Multiple hotel category
        if (! empty($filters['hotel_category_ids'])) {
            $query->whereHas('hotel', fn($q) => $q->whereIn('hotel_category_id', $filters['hotel_category_ids']));
        }

        // Boolean filters
        if (! empty($filters['is_hot'])) {
            $query->where('is_hot', true);
        }
        if (! empty($filters['instant_confirmation'])) {
            $query->where('instant_confirmation', true);
        }
        if (! empty($filters['no_stop_sale'])) {
            $query->where('no_stop_sale', true);
        }

        // Flight-related filters
        if (! empty($filters['with_flight'])) {
            $query->has('flights');
        }

        // Sort
        $allowedSorts = ['price', 'date_from', 'nights', 'hotel_name'];
        $sortField = in_array($sortBy, $allowedSorts) ? $sortBy : 'price';

        if ($sortField === 'hotel_name') {
            $query->join('hotels', 'tours.hotel_id', '=', 'hotels.id')
                ->orderBy('hotels.name', $sortDir)
                ->select('tours.*');
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        return $query->with([
            'country', 'resort', 'hotel', 'hotel.category', 'hotel.currency',
            'tourType', 'programType', 'transportType',
            'departureCity', 'currency', 'mealType', 'tourPrices.roomType',
            'flights.currency',
        ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get available departure dates for a given country and departure city.
     */
    public function getAvailableDates(?int $countryId, ?int $departureCityId): array
    {
        $query = Tour::query()->where('is_available', true)->whereNotNull('date_from');

        if ($countryId) {
            $query->where('country_id', $countryId);
        }
        if ($departureCityId) {
            $query->where('departure_city_id', $departureCityId);
        }

        return $query->distinct()
            ->pluck('date_from')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Get available nights range for given filters.
     */
    public function getNightsRange(?int $countryId, ?string $dateFrom): array
    {
        $query = Tour::query()->where('is_available', true);

        if ($countryId) {
            $query->where('country_id', $countryId);
        }
        if ($dateFrom) {
            $query->where('date_from', $dateFrom);
        }

        $min = (int) $query->min('nights') ?: 3;
        $max = (int) $query->max('nights') ?: 21;

        return ['min' => $min, 'max' => $max];
    }
}
