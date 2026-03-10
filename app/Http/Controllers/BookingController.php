<?php

namespace App\Http\Controllers;

use App\Models\AdditionalService;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\StopSale;
use App\Models\Tour;
use App\Models\TourPrice;
use App\Models\Tourist;
use App\Services\CurrencyConverter;
use App\Services\TourPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        // Block booking form if any linked flight is sold out
        $soldOutFlight = $tour->flights->first(fn($f) => $f->available_seats !== null && $f->available_seats < 1);
        if ($soldOutFlight) {
            abort(404);
        }

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
            'room_type_id' => [
                'nullable',
                Rule::exists('tour_prices', 'room_type_id')
                    ->where('tour_id', $request->input('tour_id'))
                    ->where('is_active', true),
            ],
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

        $roomTypeId = $validated['room_type_id'] ?? null;

        try {
            DB::beginTransaction();

            // [Bug #3] Pessimistic lock to prevent race conditions on concurrent bookings
            $tour = Tour::with(['currency', 'tourPrices', 'flights', 'additionalServices'])
                ->lockForUpdate()
                ->findOrFail($validated['tour_id']);

            // Guard: tour must be available and in the future
            if (!$tour->is_available || $tour->date_from < today()) {
                DB::rollBack();
                return back()->withInput()->with('error', 'This tour is no longer available for booking.');
            }

            // [Bug #4] Check stop sale against tour departure date, not today
            if ($tour->hotel_id && StopSale::where('hotel_id', $tour->hotel_id)
                    ->where('start_date', '<=', $tour->date_from)
                    ->where('end_date', '>=', $tour->date_from)
                    ->where('is_active', true)
                    ->exists()) {
                DB::rollBack();
                return back()->withInput()->with('error', 'This tour is on stop sale for the departure period.');
            }

            // [Bug #6] Validate tourist count against tour capacity
            $paxWithoutInfants = $adults + $children;
            if ($tour->adults && $adults > $tour->adults) {
                DB::rollBack();
                return back()->withInput()->with('error', "This tour allows a maximum of {$tour->adults} adults.");
            }
            if ($tour->children !== null && $children > $tour->children) {
                DB::rollBack();
                return back()->withInput()->with('error', "This tour allows a maximum of {$tour->children} children.");
            }

            // [Bug #7] Validate room type capacity
            if ($roomTypeId) {
                $roomType = RoomType::find($roomTypeId);
                if ($roomType) {
                    if ($adults > $roomType->max_adults) {
                        DB::rollBack();
                        return back()->withInput()->with('error', "The selected room type supports a maximum of {$roomType->max_adults} adults.");
                    }
                    if ($children > $roomType->max_children) {
                        DB::rollBack();
                        return back()->withInput()->with('error', "The selected room type supports a maximum of {$roomType->max_children} children.");
                    }
                }
            }

            // [Bug #1] Check and decrement tour price availability
            if ($roomTypeId) {
                $tourPrice = TourPrice::where('tour_id', $tour->id)
                    ->where('room_type_id', $roomTypeId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$tourPrice || $tourPrice->availability < 1) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'The selected room type is no longer available.');
                }

                $tourPrice->decrement('availability');
            }

            // [Bug #2] Check and decrement flight seat availability
            foreach ($tour->flights as $flight) {
                $lockedFlight = Flight::lockForUpdate()->find($flight->id);
                if ($lockedFlight->available_seats !== null && $lockedFlight->available_seats < $paxWithoutInfants) {
                    DB::rollBack();
                    return back()->withInput()->with('error', "Not enough seats available on flight {$lockedFlight->flight_number}.");
                }
                if ($lockedFlight->available_seats !== null) {
                    $lockedFlight->decrement('available_seats', $paxWithoutInfants);
                }
            }

            // Calculate total price using room type if available
            $pricingService = app(TourPricingService::class);

            if ($roomTypeId && $tour->tourPrices->where('room_type_id', $roomTypeId)->where('is_active', true)->isNotEmpty()) {
                $priceBreakdown = $pricingService->calculateBookingPrice($tour, $roomTypeId, $adults, $children, $infants);
                $totalPrice = $priceBreakdown ? $priceBreakdown['total'] : $tour->price * $numberOfTourists;
            } else {
                $totalPrice = $tour->price * $numberOfTourists;
                $roomTypeId = null;
            }

            // [Bug #8 & #9] Calculate additional services with currency conversion and markup
            $currencyConverter = app(CurrencyConverter::class);
            $markupPercent = $tour->getEffectiveMarkupPercent();
            $servicesTotalPrice = 0;
            $selectedServiceIds = array_unique($validated['additional_services'] ?? []);
            $serviceAttachments = [];

            if (!empty($selectedServiceIds)) {
                $services = AdditionalService::with('currency')->whereIn('id', $selectedServiceIds)->get();
                foreach ($services as $service) {
                    $tourService = $tour->additionalServices->firstWhere('id', $service->id);
                    $rawPrice = $tourService?->pivot->price_override ?? $service->price;

                    // Convert service price to tour currency
                    $convertedPrice = $currencyConverter->convert(
                        (float) $rawPrice,
                        $service->currency_id,
                        $tour->currency_id,
                    );

                    // Apply markup consistently with tour pricing
                    $markedUpPrice = round($convertedPrice * (1 + $markupPercent / 100), 2);

                    $quantity = $service->is_per_person ? $numberOfTourists : 1;
                    $lineTotal = round($markedUpPrice * $quantity, 2);

                    $serviceAttachments[$service->id] = [
                        'price' => $lineTotal,
                        'quantity' => $quantity,
                    ];
                    $servicesTotalPrice += $lineTotal;
                }
            }

            $totalPrice += $servicesTotalPrice;

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

            // Attach additional services
            if (!empty($serviceAttachments)) {
                $booking->additionalServices()->attach($serviceAttachments);
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
