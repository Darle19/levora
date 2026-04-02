<?php

namespace App\Services;

use App\Models\AdditionalService;
use App\Models\Booking;
use App\Models\BookingAmadeusFlight;
use App\Models\Currency;
use App\Models\Flight;
use App\Models\FlightPath;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\StopSale;
use App\Models\Tour;
use App\Models\Tourist;
use App\Services\TourPriceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
    ) {}

    /**
     * Create a booking from validated request data.
     *
     * @return array{booking: Booking, order: Order}
     *
     * @throws BookingException
     */
    public function createBooking(array $validated, int $userId, int $agencyId): array
    {
        return DB::transaction(function () use ($validated, $userId, $agencyId) {
            $tour = $this->loadAndLockTour($validated['tour_id']);
            $roomTypeId = $validated['room_type_id'] ?? null;

            $touristCounts = $this->countTouristsByAge($validated['tourists']);

            $this->validateTourAvailability($tour);
            $this->validateStopSale($tour);
            $this->validateCapacity($tour, $touristCounts);
            $this->validateRoomType($roomTypeId, $touristCounts);
            $this->decrementRoomAvailability($tour, $roomTypeId);
            $this->decrementFlightSeats($tour, $touristCounts['pax_without_infants']);

            $totalPrice = $this->calculateTotalPrice($tour, $roomTypeId, $touristCounts, $validated);
            $amadeusPrice = $this->calculateAmadeusPrice($tour, $validated, $touristCounts);
            $totalPrice = bcadd($totalPrice, $amadeusPrice, 2);

            $serviceAttachments = $this->calculateServicePrices($tour, $validated, $touristCounts['total']);
            $totalPrice = bcadd((string) $totalPrice, (string) array_sum(array_column($serviceAttachments, 'price')), 2);

            $order = $this->createOrder($agencyId, $userId, $totalPrice, $tour, $validated);
            $booking = $this->createBookingRecord($order, $tour, $roomTypeId, $totalPrice);
            $this->createTourists($booking, $validated['tourists']);
            $this->attachServices($booking, $serviceAttachments);
            $this->saveAmadeusFlightSelections($booking, $tour, $validated, $touristCounts);

            return ['booking' => $booking, 'order' => $order];
        });
    }

    /**
     * Create a booking from FlightPath + Hotels (new architecture).
     */
    public function createFlightPathBooking(array $validated, int $userId, int $agencyId): array
    {
        return DB::transaction(function () use ($validated, $userId, $agencyId) {
            $fp = FlightPath::with(['legs.flight.airline', 'stays.city', 'currency'])
                ->lockForUpdate()
                ->findOrFail($validated['flight_path_id']);

            if (! $fp->is_available || $fp->departure_date < today()) {
                throw new BookingException('This tour is no longer available.');
            }

            $touristCounts = $this->countTouristsByAge($validated['tourists']);
            $paxCount = $touristCounts['pax_without_infants'];

            // Decrement flight seats
            $flightIds = $fp->legs->pluck('flight_id')->unique();
            $lockedFlights = Flight::whereIn('id', $flightIds)->lockForUpdate()->get();
            foreach ($lockedFlights as $flight) {
                if ($flight->available_seats !== null && $flight->available_seats < $paxCount) {
                    throw new BookingException("Not enough seats on flight {$flight->flight_number}.");
                }
                if ($flight->available_seats !== null) {
                    $flight->decrement('available_seats', $paxCount);
                }
            }

            // Calculate price using the single source of truth
            $hotelIds = array_filter(explode(',', $validated['hotel_ids'] ?? ''), fn ($id) => is_numeric($id));
            $hotels = Hotel::whereIn('id', $hotelIds)->get();

            $breakdown = TourPriceCalculator::calculateFromHotels($fp, $hotels, $touristCounts['total']);
            $totalPrice = round($breakdown['price_per_person'] * $touristCounts['total'], 2);

            $usdId = Currency::where('code', 'USD')->value('id');

            // Create order
            $order = Order::create([
                'order_number' => (string) (Order::max('id') + 1),
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'currency_id' => $usdId ?? $fp->currency_id,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create booking
            $booking = Booking::create([
                'order_id' => $order->id,
                'bookable_type' => FlightPath::class,
                'bookable_id' => $fp->id,
                'status' => 'pending',
                'price' => $totalPrice,
                'currency_id' => $usdId ?? $fp->currency_id,
                'date' => $fp->departure_date,
            ]);

            // Create tourists
            $this->createTourists($booking, $validated['tourists']);

            return ['booking' => $booking, 'order' => $order];
        });
    }

    private function loadAndLockTour(int $tourId): Tour
    {
        return Tour::with(['currency', 'tourPrices', 'flights', 'amadeusSegments', 'stays', 'stays.hotel', 'stays.currency', 'additionalServices', 'additionalServices.currency'])
            ->lockForUpdate()
            ->findOrFail($tourId);
    }

    private function countTouristsByAge(array $tourists): array
    {
        $adults = 0;
        $children = 0;
        $infants = 0;

        foreach ($tourists as $t) {
            match ($t['title']) {
                'MR', 'MRS' => $adults++,
                'CHD' => $children++,
                'INF' => $infants++,
            };
        }

        return [
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'pax_without_infants' => $adults + $children,
            'total' => count($tourists),
        ];
    }

    private function validateTourAvailability(Tour $tour): void
    {
        if (! $tour->is_available || $tour->date_from < today()) {
            throw new BookingException('This tour is no longer available for booking.');
        }
    }

    private function validateStopSale(Tour $tour): void
    {
        // Collect hotel IDs from stays (multi-city) or fallback to single hotel
        $hotelIds = $tour->stays->pluck('hotel_id')->filter()->unique()->values()->toArray();
        if (empty($hotelIds) && $tour->hotel_id) {
            $hotelIds = [$tour->hotel_id];
        }

        if (empty($hotelIds)) {
            return;
        }

        $hasStopSale = StopSale::whereIn('hotel_id', $hotelIds)
            ->where('start_date', '<=', $tour->date_from)
            ->where('end_date', '>=', $tour->date_from)
            ->where('is_active', true)
            ->exists();

        if ($hasStopSale) {
            throw new BookingException('This tour is on stop sale for the departure period.');
        }
    }

    private function validateCapacity(Tour $tour, array $counts): void
    {
        if ($tour->adults && $counts['adults'] > $tour->adults) {
            throw new BookingException("This tour allows a maximum of {$tour->adults} adults.");
        }
        if ($tour->children !== null && $counts['children'] > $tour->children) {
            throw new BookingException("This tour allows a maximum of {$tour->children} children.");
        }
    }

    private function validateRoomType(?int $roomTypeId, array $counts): void
    {
        if (! $roomTypeId) {
            return;
        }

        $roomType = RoomType::find($roomTypeId);
        if (! $roomType) {
            return;
        }

        if ($counts['adults'] > $roomType->max_adults) {
            throw new BookingException("The selected room type supports a maximum of {$roomType->max_adults} adults.");
        }
        if ($counts['children'] > $roomType->max_children) {
            throw new BookingException("The selected room type supports a maximum of {$roomType->max_children} children.");
        }
    }

    private function decrementRoomAvailability(Tour $tour, ?int $roomTypeId): void
    {
        if (! $roomTypeId) {
            return;
        }

        $tourPrice = TourPrice::where('tour_id', $tour->id)
            ->where('room_type_id', $roomTypeId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $tourPrice || $tourPrice->availability < 1) {
            throw new BookingException('The selected room type is no longer available.');
        }

        $tourPrice->decrement('availability');
    }

    private function decrementFlightSeats(Tour $tour, int $paxCount): void
    {
        if ($tour->flights->isEmpty()) {
            return;
        }

        $flightIds = $tour->flights->pluck('id');
        $lockedFlights = Flight::whereIn('id', $flightIds)->lockForUpdate()->get();

        foreach ($lockedFlights as $flight) {
            if ($flight->available_seats !== null && $flight->available_seats < $paxCount) {
                throw new BookingException("Not enough seats available on flight {$flight->flight_number}.");
            }
            if ($flight->available_seats !== null) {
                $flight->decrement('available_seats', $paxCount);
            }
        }
    }

    private function calculateTotalPrice(Tour $tour, ?int $roomTypeId, array $counts, array $validated): string
    {
        return bcmul((string) $tour->price, (string) $counts['total'], 2);
    }

    private function calculateServicePrices(Tour $tour, array $validated, int $totalTourists): array
    {
        $selectedIds = array_unique($validated['additional_services'] ?? []);
        if (empty($selectedIds)) {
            return [];
        }

        $services = AdditionalService::with('currency')->whereIn('id', $selectedIds)->get();
        $markupPercent = (string) $tour->getEffectiveMarkupPercent();
        $attachments = [];

        foreach ($services as $service) {
            $tourService = $tour->additionalServices->firstWhere('id', $service->id);
            $rawPrice = (string) ($tourService?->pivot->price_override ?? $service->price);

            $convertedPrice = (string) $this->currencyConverter->convert(
                (float) $rawPrice,
                $service->currency_id,
                $tour->currency_id,
            );

            // Apply markup: price * (1 + markup/100)
            $markupMultiplier = bcadd('1', bcdiv($markupPercent, '100', 6), 6);
            $markedUpPrice = bcmul($convertedPrice, $markupMultiplier, 2);

            $quantity = $service->is_per_person ? $totalTourists : 1;
            $lineTotal = bcmul($markedUpPrice, (string) $quantity, 2);

            $attachments[$service->id] = [
                'price' => $lineTotal,
                'quantity' => $quantity,
            ];
        }

        return $attachments;
    }

    private function createOrder(int $agencyId, int $userId, string $totalPrice, Tour $tour, array $validated): Order
    {
        $notes = $validated['notes'] ?? '';
        if (! empty($validated['special_requests'])) {
            $notes .= ($notes ? "\n" : '').'Special: '.implode(', ', $validated['special_requests']);
        }

        return Order::create([
            'order_number' => (string) (Order::max('id') + 1),
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'status' => 'pending',
            'total_price' => $totalPrice,
            'currency_id' => $tour->currency_id,
            'notes' => $notes ?: null,
        ]);
    }

    private function createBookingRecord(Order $order, Tour $tour, ?int $roomTypeId, string $totalPrice): Booking
    {
        return Booking::create([
            'order_id' => $order->id,
            'bookable_type' => Tour::class,
            'bookable_id' => $tour->id,
            'room_type_id' => $roomTypeId,
            'status' => 'pending',
            'price' => $totalPrice,
            'currency_id' => $tour->currency_id,
            'date' => $tour->date_from,
        ]);
    }

    private function createTourists(Booking $booking, array $tourists): void
    {
        foreach ($tourists as $data) {
            Tourist::create([
                'booking_id' => $booking->id,
                'title' => $data['title'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'],
                'birth_country' => $data['birth_country'] ?? null,
                'gender' => $data['gender'],
                'nationality' => $data['nationality'],
                'document_type' => $data['document_type'] ?? null,
                'passport_series' => $data['passport_series'] ?? null,
                'passport_number' => $data['passport_number'],
                'passport_expiry' => $data['passport_expiry'],
                'passport_issued' => $data['passport_issued'] ?? null,
                'passport_issued_by' => $data['passport_issued_by'] ?? null,
            ]);
        }
    }

    private function attachServices(Booking $booking, array $attachments): void
    {
        if (! empty($attachments)) {
            $booking->additionalServices()->attach($attachments);
        }
    }

    private function calculateAmadeusPrice(Tour $tour, array $validated, array $counts): string
    {
        $selections = $validated['amadeus_flights'] ?? [];
        if (empty($selections)) {
            return '0';
        }

        $total = '0';
        foreach ($selections as $selection) {
            $adultPrice = (string) ($selection['price_per_adult'] ?? '0');
            $childPrice = (string) ($selection['price_per_child'] ?? $adultPrice);
            $infantPrice = (string) ($selection['price_per_infant'] ?? '0');

            $segmentTotal = bcadd(
                bcadd(
                    bcmul($adultPrice, (string) $counts['adults'], 2),
                    bcmul($childPrice, (string) $counts['children'], 2),
                    2
                ),
                bcmul($infantPrice, (string) $counts['infants'], 2),
                2
            );

            // Convert from Amadeus currency (usually USD) to tour currency
            $amadeusUsdCurrencyId = \App\Models\Currency::where('code', $selection['currency'] ?? 'USD')->value('id');
            if ($amadeusUsdCurrencyId && $amadeusUsdCurrencyId !== $tour->currency_id) {
                $segmentTotal = $this->currencyConverter->convert(
                    $segmentTotal,
                    $amadeusUsdCurrencyId,
                    $tour->currency_id,
                );
            }

            $total = bcadd($total, $segmentTotal, 2);
        }

        // Apply tour markup to Amadeus total
        $markupPercent = (string) $tour->getEffectiveMarkupPercent();
        $markupMultiplier = bcadd('1', bcdiv($markupPercent, '100', 6), 6);

        return bcmul($total, $markupMultiplier, 2);
    }

    private function saveAmadeusFlightSelections(Booking $booking, Tour $tour, array $validated, array $counts): void
    {
        $selections = $validated['amadeus_flights'] ?? [];
        if (empty($selections)) {
            return;
        }

        foreach ($selections as $selection) {
            $adultPrice = (string) ($selection['price_per_adult'] ?? '0');
            $childPrice = (string) ($selection['price_per_child'] ?? $adultPrice);
            $infantPrice = (string) ($selection['price_per_infant'] ?? '0');

            $priceTotal = bcadd(
                bcadd(
                    bcmul($adultPrice, (string) $counts['adults'], 2),
                    bcmul($childPrice, (string) $counts['children'], 2),
                    2
                ),
                bcmul($infantPrice, (string) $counts['infants'], 2),
                2
            );

            BookingAmadeusFlight::create([
                'booking_id' => $booking->id,
                'tour_amadeus_segment_id' => $selection['segment_id'],
                'amadeus_offer_id' => $selection['offer_id'] ?? null,
                'airline' => $selection['airline'],
                'airline_name' => $selection['airline_name'],
                'flight_number' => $selection['flight_number'],
                'origin' => $selection['origin'],
                'destination' => $selection['destination'],
                'departure_date' => $selection['departure_date'],
                'departure_time' => $selection['departure_time'],
                'arrival_date' => $selection['arrival_date'],
                'arrival_time' => $selection['arrival_time'],
                'duration' => $selection['duration'] ?? null,
                'stops' => $selection['stops'] ?? 0,
                'cabin_class' => $selection['cabin_class'],
                'price_per_adult' => $adultPrice,
                'price_per_child' => $selection['price_per_child'] ?? null,
                'price_per_infant' => $selection['price_per_infant'] ?? null,
                'price_total' => $priceTotal,
                'currency' => $selection['currency'],
                'raw_offer_data' => $selection,
            ]);
        }
    }
}
