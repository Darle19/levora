<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\AdditionalService;
use App\Models\Country;
use App\Models\FlightPath;
use App\Models\Hotel;
use App\Models\Setting;
use App\Models\Tour;
use App\Services\NeoInsuranceService;
use App\Services\BookingException;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    /**
     * Booking page from FlightPath + Hotels (new architecture).
     * URL: /book?fp={flight_path_id}&h={hotel_id1},{hotel_id2}
     */
    public function createFromFlightPath(Request $request): View
    {
        $fpId = $request->query('fp');
        $hotelIdStr = $request->query('h', '');

        if (! $fpId) {
            abort(404, 'Flight path required');
        }

        $flightPath = FlightPath::with([
            'legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport',
            'stays.city.country', 'currency', 'departureCity',
        ])->findOrFail($fpId);

        if (! $flightPath->is_available) {
            abort(404, 'This flight path is no longer available');
        }

        // Parse hotel IDs from comma-separated string
        $hotelIds = array_filter(explode(',', $hotelIdStr), fn ($id) => is_numeric($id));
        $hotels = Hotel::whereIn('id', $hotelIds)->with('category')->get()->keyBy('id');

        // Build stay→hotel mapping
        $stayHotels = [];
        foreach ($flightPath->stays as $stay) {
            $cityHotel = $hotels->first(fn ($h) => $h->city_id === $stay->city_id);
            $stayHotels[] = [
                'stay' => $stay,
                'hotel' => $cityHotel,
                'nights' => $stay->nights,
            ];
        }

        // Load additional services per city + global (excluding insurance)
        $cityIds = $flightPath->stays->pluck('city_id')->unique()->toArray();
        $allServices = AdditionalService::where('is_active', true)
            ->where(function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds)->orWhereNull('city_id');
            })
            ->where('service_type', '!=', 'insurance')
            ->orderBy('city_id')
            ->orderBy('name_en')
            ->get()
            ->groupBy('city_id');

        // Load insurances from NeoInsurance API
        $neoInsurance = app(NeoInsuranceService::class);
        $insurances = collect();

        if ($neoInsurance->isConfigured()) {
            // Get country codes for the tour destinations
            $countryCodes = $flightPath->stays
                ->map(fn ($s) => $s->city?->country?->code)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (! empty($countryCodes)) {
                $departureDate = $flightPath->departure_date->format('Y-m-d');
                $returnDate = $flightPath->departure_date->copy()->addDays($flightPath->nights)->format('Y-m-d');

                $neoPrograms = $neoInsurance->getInsuranceOptionsForBooking(
                    $departureDate, $returnDate, $countryCodes
                );

                foreach ($neoPrograms as $program) {
                    $insurances->push((object) [
                        'id' => $program['id'],
                        'name_en' => $program['name'] . ' (' . implode(', ', $countryCodes) . ')',
                        'price' => $program['price_usd'],
                        'is_per_person' => true,
                        'is_mandatory' => false,
                        'source' => 'neoinsurance',
                        'program_id' => $program['program_id'],
                        'period' => $program['period'],
                    ]);
                }
            }
        }

        // Fallback: local insurance services from DB
        if ($insurances->isEmpty()) {
            $localInsurances = AdditionalService::where('is_active', true)
                ->where('service_type', 'insurance')
                ->where(function ($q) use ($cityIds) {
                    $q->whereIn('city_id', $cityIds)->orWhereNull('city_id');
                })
                ->orderBy('name_en')
                ->get();

            foreach ($localInsurances as $li) {
                $insurances->push($li);
            }
        }

        // Build services per stay, tracking one-time and per-city services to avoid duplicates
        $stayServices = [];
        $oneTimeServices = collect();
        $mandatoryServicesCost = 0;
        $seenServiceIds = []; // track all seen mandatory service IDs to avoid double-counting

        foreach ($flightPath->stays as $stay) {
            $cityServices = $allServices->get($stay->city_id, collect());
            // Also include global services (city_id = null) for first stay only
            $globalServices = $allServices->get('', collect());

            $combined = $cityServices->merge($globalServices)->unique('id');

            // Separate one-time services (show only once, not per city)
            $perCity = $combined->where('is_one_time', false);
            $oneTime = $combined->where('is_one_time', true);

            foreach ($oneTime as $svc) {
                if (! in_array($svc->id, $seenServiceIds)) {
                    $seenServiceIds[] = $svc->id;
                    $oneTimeServices->push($svc);
                    if ($svc->is_mandatory) {
                        $mandatoryServicesCost += (float) $svc->price;
                    }
                }
            }

            $mandatory = $perCity->where('is_mandatory', true);
            $optional = $perCity->where('is_mandatory', false);

            foreach ($mandatory as $svc) {
                if (! in_array($svc->id, $seenServiceIds)) {
                    $seenServiceIds[] = $svc->id;
                    $mandatoryServicesCost += (float) $svc->price;
                }
            }

            $stayServices[] = [
                'stay' => $stay,
                'mandatory' => $mandatory,
                'optional' => $optional,
            ];
        }

        // Calculate hotel cost — full room price per stay (split dynamically by JS based on tourist count)
        $hiddenFee = (float) Setting::getValue('tour_hidden_fee', 60);
        $agentFee = (float) Setting::getValue('tour_agent_fee', 50);
        $hotelRoomTotal = 0; // full DBL room cost across all stays
        foreach ($stayHotels as $sh) {
            if ($sh['hotel']) {
                $hotelRoomTotal += (float) $sh['hotel']->price_per_person * $sh['nights'];
            }
        }
        // Default display assumes 2 people sharing (per-person = room/2)
        $hotelCostPerPerson = $hotelRoomTotal / 2;
        $pricePerPerson = $flightPath->flight_total + $hotelCostPerPerson + $hiddenFee + $agentFee + $mandatoryServicesCost;

        $countries = Country::where('is_active', true)->orderBy('name_en')->get();

        return view('bookings.create_fp', compact(
            'flightPath', 'stayHotels', 'hotels', 'pricePerPerson',
            'hotelCostPerPerson', 'hotelRoomTotal', 'hiddenFee', 'agentFee', 'mandatoryServicesCost',
            'stayServices', 'oneTimeServices', 'insurances', 'countries'
        ));
    }

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
            'stays' => fn ($q) => $q->orderBy('stay_order'),
            'stays.hotel',
            'stays.hotel.category',
            'stays.city',
            'stays.mealType',
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
            'amadeusSegments' => fn ($q) => $q->where('is_active', true),
            'amadeusSegments.originAirport',
            'amadeusSegments.destinationAirport',
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
            $validated = $request->validated();

            // Route to correct booking method based on input
            if (! empty($validated['flight_path_id'])) {
                $result = $this->bookingService->createFlightPathBooking(
                    $validated,
                    auth()->id(),
                    auth()->user()->agency_id,
                );
            } else {
                $result = $this->bookingService->createBooking(
                    $validated,
                    auth()->id(),
                    auth()->user()->agency_id,
                );
            }

            return redirect()->route('bookings.confirmation', $result['booking'])
                ->with('success', 'Your booking has been created successfully!');

        } catch (BookingException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'flight_path_id' => $request->validated('flight_path_id'),
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
            'amadeusFlights',
        ]);

        if ($booking->bookable instanceof Tour) {
            $booking->bookable->load([
                'country',
                'resort',
                'hotel',
                'hotel.category',
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
