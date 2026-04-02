<?php

namespace App\Services;

use App\Models\AdditionalService;
use App\Models\FlightPath;
use App\Models\Hotel;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Single source of truth for tour pricing.
 *
 * Formula per person:
 *   flight_total (with baggage fees)
 * + hotel_cost (full room if 1 pax, room/2 if 2+ pax)
 * + hidden_fee (not shown to agent)
 * + agent_fee (shown to agent)
 * + mandatory_services (filtered by city, excursions only if stay > 3n)
 */
class TourPriceCalculator
{
    /**
     * Calculate price per person for a flight path + hotels combination.
     *
     * @param FlightPath $flightPath  Loaded with legs.flight.airline, stays
     * @param array $stayHotels  Array of ['hotel' => Hotel, 'nights' => int, 'city_id' => int]
     * @param int $adults  Number of adults (1 = full room, 2+ = split)
     * @return array  Price breakdown
     */
    public static function calculate(FlightPath $flightPath, array $stayHotels, int $adults = 2): array
    {
        $flightTotal = $flightPath->flight_total;

        // Hotel cost: full room price, then divide based on pax
        $hotelRoomTotal = 0;
        foreach ($stayHotels as $sh) {
            $hotel = $sh['hotel'] ?? null;
            $nights = $sh['nights'] ?? 0;
            if ($hotel) {
                $hotelRoomTotal += (float) $hotel->price_per_person * $nights;
            }
        }
        // ceil(people/2) rooms needed: 1p=1room, 2p=1room, 3p=2rooms, 4p=2rooms
        $rooms = (int) ceil($adults / 2);
        $hotelPerPerson = ($rooms * $hotelRoomTotal) / max($adults, 1);

        // Fees
        $hiddenFee = (float) Setting::getValue('tour_hidden_fee', 60);
        $agentFee = (float) Setting::getValue('tour_agent_fee', 50);

        // Mandatory services
        $cityIds = $flightPath->stays->pluck('city_id')->unique();
        $stayNightsByCity = $flightPath->stays->pluck('nights', 'city_id');

        $mandatoryServices = AdditionalService::where('is_active', true)
            ->where('is_mandatory', true)
            ->where('service_type', '!=', 'insurance')
            ->where(function ($q) use ($cityIds) {
                $q->whereIn('city_id', $cityIds)->orWhereNull('city_id');
            })
            ->get();

        $mandatoryCost = 0;
        $seenIds = [];
        foreach ($mandatoryServices as $svc) {
            // Excursions only if city stay > 3 nights
            if ($svc->is_excursion) {
                $cityNights = $svc->city_id ? ($stayNightsByCity[$svc->city_id] ?? 0) : $flightPath->nights;
                if ($cityNights <= 3) {
                    continue;
                }
            }

            // One-time: count once
            if ($svc->is_one_time && in_array($svc->id, $seenIds)) {
                continue;
            }

            // Deduplicate for repeated cities
            if (in_array($svc->id, $seenIds)) {
                continue;
            }

            $seenIds[] = $svc->id;
            $mandatoryCost += (float) $svc->price;
        }

        $pricePerPerson = $flightTotal + $hotelPerPerson + $hiddenFee + $agentFee + $mandatoryCost;

        return [
            'price_per_person' => round($pricePerPerson, 2),
            'flight_total' => round($flightTotal, 2),
            'hotel_room_total' => round($hotelRoomTotal, 2),
            'hotel_per_person' => round($hotelPerPerson, 2),
            'hidden_fee' => $hiddenFee,
            'agent_fee' => $agentFee,
            'mandatory_services_cost' => round($mandatoryCost, 2),
            'adults' => $adults,
        ];
    }

    /**
     * Shortcut: calculate from FlightPath + Hotel models matched by city.
     */
    public static function calculateFromHotels(FlightPath $flightPath, Collection $hotels, int $adults = 2): array
    {
        $stayHotels = [];
        foreach ($flightPath->stays as $stay) {
            $hotel = $hotels->first(fn ($h) => $h->city_id === $stay->city_id);
            $stayHotels[] = [
                'hotel' => $hotel,
                'nights' => $stay->nights,
                'city_id' => $stay->city_id,
            ];
        }

        return static::calculate($flightPath, $stayHotels, $adults);
    }
}
