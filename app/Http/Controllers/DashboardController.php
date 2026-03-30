<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FlightPath;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $agencyId = $user->agency_id;

        // Stats for this agency
        $totalClaims = Order::where('agency_id', $agencyId)->count();
        $activeBookings = Order::where('agency_id', $agencyId)->where('status', 'confirmed')->count();
        $pendingBookings = Order::where('agency_id', $agencyId)->where('status', 'pending')->count();
        $monthlyRevenue = Order::where('agency_id', $agencyId)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_price');

        // Recent orders for this agency
        $recentOrders = Order::where('agency_id', $agencyId)
            ->with('bookings')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Available tour routes
        $tourRoutes = FlightPath::where('is_available', true)
            ->where('departure_date', '>=', today())
            ->select('route_name')
            ->selectRaw('count(*) as count')
            ->selectRaw('min(departure_date) as next_date')
            ->groupBy('route_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalClaims', 'activeBookings', 'pendingBookings', 'monthlyRevenue',
            'recentOrders', 'tourRoutes'
        ));
    }
}
