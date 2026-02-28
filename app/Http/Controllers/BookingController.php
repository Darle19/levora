<?php

namespace App\Http\Controllers;

use App\Models\AdditionalService;
use App\Models\Booking;
use App\Models\Order;
use App\Models\StopSale;
use App\Models\Tour;
use App\Models\Tourist;
use App\Services\TourPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Show the booking form for a tour.
     */
    public function create(Tour $tour): View
    {
        // Guard: tour must be available and in the future
        if (!$tour->is_available || $tour->date_from < today()) {
            abort(404);
        }

        // Load tour relationships including flights
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
            'tourPrices' => fn($q) => $q->where('is_active', true)->where('availability', '>', 0),
            'tourPrices.roomType',
            'tourPrices.currency',
        ]);

        $countries = \App\Models\Country::where('is_active', 1)->orderBy('name_en')->get();

        return view('bookings.create', compact('tour', 'countries'));
    }

    /**
     * Store a new booking.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'tourists' => 'required|array|min:1',
            'tourists.*.title' => 'required|in:MR,MRS,CHD,INF',
            'tourists.*.gender' => 'required|in:male,female',
            'tourists.*.last_name' => 'required|string|max:255',
            'tourists.*.first_name' => 'required|string|max:255',
            'tourists.*.birth_date' => 'required|date|before:today',
            'tourists.*.birth_country' => 'nullable|string|max:100',
            'tourists.*.nationality' => 'required|string|max:100',
            'tourists.*.document_type' => 'nullable|string|max:50',
            'tourists.*.passport_series' => 'nullable|string|max:20',
            'tourists.*.passport_number' => 'required|string|max:50',
            'tourists.*.passport_expiry' => 'required|date|after:today',
            'tourists.*.passport_issued' => 'nullable|date|before:today',
            'tourists.*.passport_issued_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'special_requests' => 'nullable|array',
            'additional_services' => 'nullable|array',
            'additional_services.*' => 'exists:additional_services,id',
        ]);

        // Load the tour
        $tour = Tour::with(['currency', 'tourPrices', 'flights'])->findOrFail($validated['tour_id']);

        // Guard: tour must be available, in the future, and not stopped
        if (!$tour->is_available || $tour->date_from < today()) {
            return back()->withInput()->with('error', 'This tour is no longer available for booking.');
        }

        if ($tour->hotel_id && StopSale::where('hotel_id', $tour->hotel_id)
                ->where('date_from', '<=', today())
                ->where('date_to', '>=', today())
                ->exists()) {
            return back()->withInput()->with('error', 'This tour is currently on stop sale.');
        }

        // Count tourists by age category
        $numberOfTourists = count($validated['tourists']);
        $adults = 0;
        $children = 0;
        $infants = 0;
        foreach ($validated['tourists'] as $t) {
            match ($t['title']) {
                'MR', 'MRS' => $adults++,
                'CHD' => $children++,
                'INF' => $infants++,
            };
        }

        // Calculate total price using room type if available
        $roomTypeId = $validated['room_type_id'] ?? null;
        $pricingService = app(TourPricingService::class);

        if ($roomTypeId && $tour->tourPrices->where('room_type_id', $roomTypeId)->where('is_active', true)->isNotEmpty()) {
            $priceBreakdown = $pricingService->calculateBookingPrice($tour, $roomTypeId, $adults, $children, $infants);
            $totalPrice = $priceBreakdown ? $priceBreakdown['total'] : $tour->price * $numberOfTourists;
        } else {
            $totalPrice = $tour->price * $numberOfTourists;
            $roomTypeId = null;
        }

        try {
            DB::beginTransaction();

            // Generate unique order number (ULID is monotonic and collision-proof)
            $orderNumber = 'ORD-' . Str::ulid();

            // Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'agency_id' => auth()->user()->agency_id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'total_price' => $totalPrice,
                'currency_id' => $tour->currency_id,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Generate unique booking number
            $bookingNumber = 'BK-' . Str::ulid();

            // Create Booking (polymorphic for Tour)
            $booking = Booking::create([
                'order_id' => $order->id,
                'bookable_type' => Tour::class,
                'bookable_id' => $tour->id,
                'room_type_id' => $roomTypeId,
                'status' => 'pending',
                'price' => $totalPrice,
                'currency_id' => $tour->currency_id,
                'date' => $tour->date_from,
            ]);

            // Append special requests to notes
            $notes = $validated['notes'] ?? '';
            if (!empty($validated['special_requests'])) {
                $notes .= ($notes ? "\n" : '') . 'Special: ' . implode(', ', $validated['special_requests']);
            }
            $order->update(['notes' => $notes ?: null]);

            // Create Tourists
            foreach ($validated['tourists'] as $touristData) {
                Tourist::create([
                    'booking_id' => $booking->id,
                    'title' => $touristData['title'],
                    'first_name' => $touristData['first_name'],
                    'last_name' => $touristData['last_name'],
                    'birth_date' => $touristData['birth_date'],
                    'birth_country' => $touristData['birth_country'] ?? null,
                    'gender' => $touristData['gender'],
                    'nationality' => $touristData['nationality'],
                    'document_type' => $touristData['document_type'] ?? null,
                    'passport_series' => $touristData['passport_series'] ?? null,
                    'passport_number' => $touristData['passport_number'],
                    'passport_expiry' => $touristData['passport_expiry'],
                    'passport_issued' => $touristData['passport_issued'] ?? null,
                    'passport_issued_by' => $touristData['passport_issued_by'] ?? null,
                ]);
            }

            // Attach selected additional services
            $selectedServiceIds = array_unique($validated['additional_services'] ?? []);
            if (!empty($selectedServiceIds)) {
                $services = AdditionalService::whereIn('id', $selectedServiceIds)->get();
                foreach ($services as $service) {
                    $tourService = $tour->additionalServices()->where('additional_service_id', $service->id)->first();
                    $price = $tourService?->pivot->price_override ?? $service->price;
                    $quantity = $service->is_per_person ? $numberOfTourists : 1;
                    $booking->additionalServices()->attach($service->id, [
                        'price' => $price * $quantity,
                        'quantity' => $quantity,
                    ]);
                    $totalPrice += $price * $quantity;
                }
                // Update booking and order with service costs
                $booking->update(['price' => $totalPrice]);
                $order->update(['total_price' => $totalPrice]);
            }

            DB::commit();

            // Redirect to confirmation page
            return redirect()->route('bookings.confirmation', $booking)
                ->with('success', 'Your booking has been created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'tour_id' => $validated['tour_id'],
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'An error occurred while processing your booking. Please try again.');
        }
    }

    /**
     * Show booking confirmation page.
     */
    public function confirmation(Booking $booking): View
    {
        $this->authorize('view', $booking);

        // Load all relationships
        $booking->load([
            'order',
            'order.currency',
            'bookable',
            'currency',
            'tourists'
        ]);

        // Load tour-specific relationships if bookable is a Tour
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
                'mealType'
            ]);
        }

        return view('bookings.confirmation', compact('booking'));
    }
}
