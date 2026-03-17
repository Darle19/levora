<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Tour;
use App\Services\BookingException;
use App\Services\BookingService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function create(Tour $tour): View
    {
        if (! $tour->is_available || $tour->date_from < today()) {
            abort(404);
        }

        $tour->load([
            'country',
            'resort',
            'hotel',
            'hotel.category',
            'tourType',
            'programType',
            'transportType',
            'departureCity',
            'currency',
            'mealType',
            'flights',
            'flights.airline',
            'flights.fromAirport',
            'flights.fromAirport.city',
            'flights.toAirport',
            'flights.toAirport.city',
            'additionalServices',
            'additionalServices.currency',
            'tourPrices' => fn ($q) => $q->where('is_active', true)->where('availability', '>', 0),
            'tourPrices.roomType',
            'tourPrices.currency',
        ]);

        $soldOutFlight = $tour->flights->first(fn ($f) => $f->available_seats !== null && $f->available_seats < 1);
        if ($soldOutFlight) {
            abort(404);
        }

        $countries = \App\Models\Country::where('is_active', 1)->orderBy('name_en')->get();

        return view('bookings.create', compact('tour', 'countries'));
    }

    public function store(StoreBookingRequest $request)
    {
        try {
            $result = $this->bookingService->createBooking(
                $request->validated(),
                auth()->id(),
                auth()->user()->agency_id,
            );

            return redirect()->route('bookings.confirmation', $result['booking'])
                ->with('success', 'Your booking has been created successfully!');

        } catch (BookingException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'tour_id' => $request->validated('tour_id'),
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'An error occurred while processing your booking. Please try again.');
        }
    }

    public function confirmation(\App\Models\Booking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load([
            'order',
            'order.currency',
            'bookable',
            'currency',
            'tourists',
        ]);

        if ($booking->bookable instanceof Tour) {
            $booking->bookable->load([
                'country',
                'resort',
                'hotel',
                'hotel.category',
                'tourType',
                'programType',
                'transportType',
                'departureCity',
                'currency',
                'mealType',
            ]);
        }

        return view('bookings.confirmation', compact('booking'));
    }
}
