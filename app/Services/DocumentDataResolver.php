<?php

namespace App\Services;

use App\Models\AdditionalService;
use App\Models\Booking;
use App\Models\FlightPath;
use App\Models\Hotel;

/**
 * Normalizes booking data into a uniform structure for document templates.
 * Handles both FlightPath and Hotel bookable types.
 */
class DocumentDataResolver
{
    public function resolve(Booking $booking): array
    {
        $booking->load([
            'order.agency', 'order.user', 'order.currency',
            'tourists', 'hotels.category', 'hotels.city',
            'additionalServices',
        ]);

        $base = [
            'booking' => $booking,
            'order' => $booking->order,
            'tourists' => $booking->tourists,
            'agency' => $booking->order->agency,
            'order_number' => $booking->order->order_number,
            'issue_date' => $booking->created_at,
        ];

        return match ($booking->bookable_type) {
            FlightPath::class => array_merge($base, $this->resolveFlightPath($booking)),
            Hotel::class => array_merge($base, $this->resolveHotel($booking)),
            default => array_merge($base, [
                'type' => 'unknown',
                'flights' => collect(),
                'hotels' => [],
                'transfers' => collect(),
                'insurances' => collect(),
                'departure_date' => $booking->date,
                'return_date' => $booking->date,
                'total_nights' => 0,
                'destination_country' => null,
            ]),
        };
    }

    private function resolveFlightPath(Booking $booking): array
    {
        $fp = FlightPath::with([
            'legs.flight.airline', 'legs.flight.fromAirport.city',
            'legs.flight.toAirport.city', 'stays.city.country',
            'departureCity',
        ])->find($booking->bookable_id);

        if (! $fp) {
            return $this->emptyData('flight_path');
        }

        // Flights
        $flights = $fp->legs->sortBy('leg_order')->map(function ($leg) use ($booking) {
            $f = $leg->flight;
            return (object) [
                'date' => $f->departure_date,
                'flight_number' => ($f->airline->code ?? '') . ' ' . $f->flight_number,
                'airline_name' => $f->airline->name ?? '',
                'airline_code' => $f->airline->code ?? '',
                'departure_city' => $f->fromAirport->city->name_en ?? '',
                'departure_airport' => $f->fromAirport->code ?? '',
                'departure_time' => $f->departure_time ? substr($f->departure_time, 0, 5) : '',
                'arrival_city' => $f->toAirport->city->name_en ?? '',
                'arrival_airport' => $f->toAirport->code ?? '',
                'arrival_time' => $f->arrival_time ? substr($f->arrival_time, 0, 5) : '',
                'class_type' => ucfirst($f->class_type ?? 'economy'),
                'class_code' => strtoupper(substr($f->class_type ?? 'economy', 0, 1)),
                'seats' => $booking->tourists->count(),
                'baggage' => $f->baggage ?? '1PC 20 kg',
                'direction' => $leg->direction,
            ];
        });

        // Hotels from booking_hotels pivot
        $hotels = $booking->hotels->map(function ($hotel) {
            return (object) [
                'hotel_name' => $hotel->name_en,
                'stars' => $hotel->category->stars ?? 0,
                'city' => $hotel->city->name_en ?? '',
                'country' => $hotel->city->country->name_en ?? '',
                'room_type' => 'DBL',
                'meal' => 'BB',
                'nights' => $hotel->pivot->nights,
                'rooms' => 1,
                'check_in' => $hotel->pivot->check_in_date,
                'check_out' => $hotel->pivot->check_out_date,
                'address' => $hotel->address ?? '',
                'phone' => $hotel->phone ?? '',
                'email' => $hotel->email ?? '',
            ];
        })->values()->toArray();

        // All additional services (mandatory) attached to the booking's cities
        $cityIds = $fp->stays->pluck('city_id')->unique()->toArray();
        $allServices = AdditionalService::where('is_active', true)
            ->where('is_mandatory', true)
            ->where(function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds)->orWhereNull('city_id');
            })
            ->with('city')
            ->get();

        $transfers = $allServices
            ->where('service_type', 'transfer')
            ->map(function ($svc) use ($fp) {
                return (object) [
                    'date' => $fp->departure_date->format('d.m.Y'),
                    'type' => $svc->name_en,
                    'direction' => $svc->city->name_en ?? 'Transfer',
                ];
            })->values();

        $additionalServices = $allServices
            ->whereNotIn('service_type', ['transfer', 'insurance'])
            ->map(function ($svc) {
                return (object) [
                    'name' => $svc->name_en,
                    'city' => $svc->city->name_en ?? '—',
                    'description' => $svc->description ?? '',
                ];
            })->values();

        // Insurances
        $insurances = AdditionalService::where('is_active', true)
            ->where('service_type', 'insurance')
            ->where(function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds)->orWhereNull('city_id');
            })
            ->get()
            ->map(function ($svc) use ($fp) {
                return (object) [
                    'period' => $fp->departure_date->format('d.m.Y') . ' - ' . $fp->departure_date->copy()->addDays($fp->nights)->format('d.m.Y'),
                    'name' => $svc->name_en,
                ];
            });

        $lastCity = $fp->stays->sortByDesc('stay_order')->first();

        // City contacts (local agents)
        $cityContacts = $fp->stays->map(function ($stay) {
            $city = $stay->city;
            if (! $city || ! $city->agent_phone) {
                return null;
            }
            return (object) [
                'city' => $city->name_en,
                'agent_name' => $city->agent_name,
                'agent_phone' => $city->agent_phone,
            ];
        })->filter()->values();

        return [
            'type' => 'flight_path',
            'flight_path' => $fp,
            'flights' => $flights,
            'hotels' => $hotels,
            'transfers' => $transfers,
            'additional_services' => $additionalServices,
            'insurances' => $insurances,
            'city_contacts' => $cityContacts,
            'departure_date' => $fp->departure_date,
            'return_date' => $fp->departure_date->copy()->addDays($fp->nights),
            'total_nights' => $fp->nights,
            'destination_country' => $lastCity?->city?->country?->name_en,
            'departure_city' => $fp->departureCity->name_en ?? '',
        ];
    }

    private function resolveHotel(Booking $booking): array
    {
        $hotel = Hotel::with(['category', 'city.country'])->find($booking->bookable_id);

        if (! $hotel) {
            return $this->emptyData('hotel');
        }

        $nights = 7; // default, could be stored
        $checkIn = $booking->date;
        $checkOut = $checkIn?->copy()->addDays($nights);

        $hotels = [(object) [
            'hotel_name' => $hotel->name_en,
            'stars' => $hotel->category->stars ?? 0,
            'city' => $hotel->city->name_en ?? '',
            'country' => $hotel->city->country->name_en ?? '',
            'room_type' => $booking->roomType->code ?? 'DBL',
            'meal' => 'BB',
            'nights' => $nights,
            'rooms' => 1,
            'check_in' => $checkIn?->format('Y-m-d'),
            'check_out' => $checkOut?->format('Y-m-d'),
            'address' => $hotel->address ?? '',
            'phone' => $hotel->phone ?? '',
            'email' => $hotel->email ?? '',
        ]];

        $cityContacts = collect();
        if ($hotel->city && $hotel->city->agent_phone) {
            $cityContacts->push((object) [
                'city' => $hotel->city->name_en,
                'agent_name' => $hotel->city->agent_name,
                'agent_phone' => $hotel->city->agent_phone,
            ]);
        }

        return [
            'type' => 'hotel',
            'flights' => collect(),
            'hotels' => $hotels,
            'transfers' => collect(),
            'additional_services' => collect(),
            'insurances' => collect(),
            'city_contacts' => $cityContacts,
            'departure_date' => $checkIn,
            'return_date' => $checkOut,
            'total_nights' => $nights,
            'destination_country' => $hotel->city->country->name_en ?? '',
            'departure_city' => '',
        ];
    }

    private function emptyData(string $type): array
    {
        return [
            'type' => $type,
            'flights' => collect(),
            'hotels' => [],
            'transfers' => collect(),
            'additional_services' => collect(),
            'insurances' => collect(),
            'city_contacts' => collect(),
            'departure_date' => null,
            'return_date' => null,
            'total_nights' => 0,
            'destination_country' => null,
            'departure_city' => '',
        ];
    }
}
