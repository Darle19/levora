<?php

namespace App\Services;

use App\Models\AdditionalService;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Order;
use App\Models\RoomType;
use App\Models\StopSale;
use App\Models\Tour;
use App\Models\TourPrice;
use App\Models\Tourist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly TourPricingService $pricingService,
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
            $serviceAttachments = $this->calculateServicePrices($tour, $validated, $touristCounts['total']);

            $totalPrice = bcadd((string) $totalPrice, (string) array_sum(array_column($serviceAttachments, 'price')), 2);

            $order = $this->createOrder($agencyId, $userId, $totalPrice, $tour, $validated);
            $booking = $this->createBookingRecord($order, $tour, $roomTypeId, $totalPrice);
            $this->createTourists($booking, $validated['tourists']);
            $this->attachServices($booking, $serviceAttachments);

            return ['booking' => $booking, 'order' => $order];
        });
    }

    private function loadAndLockTour(int $tourId): Tour
    {
        return Tour::with(['currency', 'tourPrices', 'flights', 'additionalServices', 'additionalServices.currency'])
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
        if (! $tour->hotel_id) {
            return;
        }

        $hasStopSale = StopSale::where('hotel_id', $tour->hotel_id)
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
        if ($roomTypeId && $tour->tourPrices->where('room_type_id', $roomTypeId)->where('is_active', true)->isNotEmpty()) {
            $breakdown = $this->pricingService->calculateBookingPrice(
                $tour, $roomTypeId, $counts['adults'], $counts['children'], $counts['infants']
            );

            return $breakdown ? (string) $breakdown['total'] : bcmul((string) $tour->price, (string) $counts['total'], 2);
        }

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
            'order_number' => 'ORD-'.Str::ulid(),
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
}
