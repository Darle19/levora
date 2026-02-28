<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClaimController extends Controller
{
    /**
     * Display a listing of claims.
     */
    public function index(): View
    {
        $claims = Order::where('agency_id', Auth::user()->agency_id)
            ->with(['user', 'bookings'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('claims.index', compact('claims'));
    }

    /**
     * Display the specified claim.
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['user', 'bookings.tourists', 'bookings.documents.tourist', 'payments']);

        $paymentPercentage = $order->getPaymentPercentage();

        return view('claims.show', compact('order', 'paymentPercentage'));
    }
}
