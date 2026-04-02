<?php

namespace App\Http\Controllers;

use App\Models\AdditionalService;
use App\Models\FlightPath;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function index(): View
    {
        $claims = Order::where('agency_id', Auth::user()->agency_id)
            ->with(['user', 'bookings', 'currency'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('claims.index', compact('claims'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'user', 'agency', 'currency', 'payments.currency',
            'bookings.tourists', 'bookings.documents.tourist', 'bookings.currency',
            'bookings.additionalServices',
        ]);

        $paymentPercentage = $order->getPaymentPercentage();
        $booking = $order->bookings->first();

        $flightPath = null;
        $hotel = null;
        $stayHotels = [];
        $services = collect();
        $insurances = collect();

        if ($booking && $booking->bookable_type === \App\Models\Hotel::class) {
            $hotel = Hotel::with(['category', 'city.country', 'roomTypes'])->find($booking->bookable_id);
        } elseif ($booking && $booking->bookable_type === FlightPath::class) {
            $flightPath = FlightPath::with([
                'legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport',
                'stays.city', 'departureCity',
            ])->find($booking->bookable_id);

            if ($flightPath) {
                foreach ($flightPath->stays as $stay) {
                    $hotel = Hotel::where('city_id', $stay->city_id)
                        ->where('is_active', true)
                        ->with('category')
                        ->first();
                    $stayHotels[] = [
                        'stay' => $stay,
                        'hotel' => $hotel,
                        'nights' => $stay->nights,
                    ];
                }

                $cityIds = $flightPath->stays->pluck('city_id')->unique()->toArray();

                $services = AdditionalService::where('is_active', true)
                    ->where('is_mandatory', true)
                    ->whereIn('city_id', $cityIds)
                    ->where('service_type', '!=', 'insurance')
                    ->get();

                $insurances = AdditionalService::where('is_active', true)
                    ->where('service_type', 'insurance')
                    ->where(function ($q) use ($cityIds) {
                        $q->whereIn('city_id', $cityIds)->orWhereNull('city_id');
                    })
                    ->get();
            }
        }

        return view('claims.show', compact(
            'order', 'paymentPercentage', 'booking',
            'flightPath', 'hotel', 'stayHotels', 'services', 'insurances'
        ));
    }
}
