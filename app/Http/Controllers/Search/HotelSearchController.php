<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Hotel;
use App\Models\HotelCommissionTier;
use App\Models\Resort;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelSearchController extends Controller
{
    public function index(Request $request): View
    {
        $countries = Country::where('is_active', true)
            ->whereHas('cities.hotels', fn ($q) => $q->where('is_active', true))
            ->orderBy('name_en')
            ->get();

        $selectedCountryId = $request->query('country');
        $selectedResortId = $request->query('resort');
        $nights = (int) ($request->query('nights', 7));

        $resorts = collect();
        $hotels = collect();
        $commission = 0;

        if ($selectedCountryId) {
            $resorts = Resort::where('country_id', $selectedCountryId)
                ->where('is_active', true)
                ->whereHas('hotels', fn ($q) => $q->where('is_active', true))
                ->orderBy('name_en')
                ->get();

            $hotelQuery = Hotel::where('is_active', true)
                ->whereHas('resort', fn ($q) => $q->where('country_id', $selectedCountryId))
                ->with(['category', 'resort', 'city', 'roomTypes', 'mealTypes']);

            if ($selectedResortId) {
                $hotelQuery->where('resort_id', $selectedResortId);
            }

            $hotels = $hotelQuery->orderBy('resort_id')->orderBy('name_en')->get();

            // Hidden commission based on nights
            $commission = HotelCommissionTier::getForNights($nights);
        }

        return view('search.hotels.index', compact(
            'countries', 'resorts', 'hotels',
            'selectedCountryId', 'selectedResortId', 'nights', 'commission'
        ));
    }
}
