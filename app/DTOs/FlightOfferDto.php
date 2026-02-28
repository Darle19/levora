<?php

namespace App\DTOs;

class FlightOfferDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $airline,
        public readonly string $airlineName,
        public readonly string $flightNumber,
        public readonly string $origin,
        public readonly string $originName,
        public readonly string $destination,
        public readonly string $destinationName,
        public readonly string $departureDate,
        public readonly string $departureTime,
        public readonly string $arrivalDate,
        public readonly string $arrivalTime,
        public readonly string $duration,
        public readonly int $stops,
        public readonly float $priceTotal,
        public readonly string $currency,
        public readonly float $pricePerAdult,
        public readonly ?float $pricePerChild,
        public readonly ?float $pricePerInfant,
        public readonly int $availableSeats,
        public readonly string $cabinClass,
        public readonly ?string $returnDepartureDate = null,
        public readonly ?string $returnDepartureTime = null,
        public readonly ?string $returnArrivalDate = null,
        public readonly ?string $returnArrivalTime = null,
        public readonly ?string $returnDuration = null,
        public readonly ?int $returnStops = null,
        public readonly bool $isAmadeus = false,
        public readonly ?string $amadeusOfferId = null,
    ) {}

    public static function fromAmadeus(array $offer, array $dictionaries = []): self
    {
        $itineraries = $offer['itineraries'] ?? [];
        $outbound = $itineraries[0] ?? null;
        $returnItinerary = $itineraries[1] ?? null;

        $outboundSegments = $outbound['segments'] ?? [];
        $firstSegment = $outboundSegments[0] ?? [];
        $lastSegment = end($outboundSegments) ?: $firstSegment;

        $price = $offer['price'] ?? [];
        $travelerPricings = $offer['travelerPricings'] ?? [];

        $adultPrice = 0;
        $childPrice = null;
        $infantPrice = null;

        foreach ($travelerPricings as $tp) {
            $travelerType = $tp['travelerType'] ?? '';
            $tpPrice = (float) ($tp['price']['total'] ?? 0);
            match ($travelerType) {
                'ADULT' => $adultPrice = $tpPrice,
                'CHILD' => $childPrice = $tpPrice,
                'HELD_INFANT', 'SEATED_INFANT' => $infantPrice = $tpPrice,
                default => null,
            };
        }

        $carrierCode = $firstSegment['carrierCode'] ?? '';
        $airlineName = $dictionaries['carriers'][$carrierCode] ?? $carrierCode;

        $originCode = $firstSegment['departure']['iataCode'] ?? '';
        $destinationCode = $lastSegment['arrival']['iataCode'] ?? '';
        $locations = $dictionaries['locations'] ?? [];
        $originCity = $locations[$originCode]['cityCode'] ?? $originCode;
        $destinationCity = $locations[$destinationCode]['cityCode'] ?? $destinationCode;

        return new self(
            id: $offer['id'] ?? '',
            source: 'amadeus',
            airline: $carrierCode,
            airlineName: $airlineName,
            flightNumber: $carrierCode . ($firstSegment['number'] ?? ''),
            origin: $originCode,
            originName: $originCity,
            destination: $destinationCode,
            destinationName: $destinationCity,
            departureDate: substr($firstSegment['departure']['at'] ?? '', 0, 10),
            departureTime: substr($firstSegment['departure']['at'] ?? '', 11, 5),
            arrivalDate: substr($lastSegment['arrival']['at'] ?? '', 0, 10),
            arrivalTime: substr($lastSegment['arrival']['at'] ?? '', 11, 5),
            duration: self::formatDuration($outbound['duration'] ?? ''),
            stops: max(0, count($outboundSegments) - 1),
            priceTotal: (float) ($price['grandTotal'] ?? $price['total'] ?? 0),
            currency: $price['currency'] ?? 'USD',
            pricePerAdult: $adultPrice,
            pricePerChild: $childPrice,
            pricePerInfant: $infantPrice,
            availableSeats: (int) ($offer['numberOfBookableSeats'] ?? 0),
            cabinClass: $travelerPricings[0]['fareDetailsBySegment'][0]['cabin'] ?? 'ECONOMY',
            returnDepartureDate: $returnItinerary ? substr($returnItinerary['segments'][0]['departure']['at'] ?? '', 0, 10) : null,
            returnDepartureTime: $returnItinerary ? substr($returnItinerary['segments'][0]['departure']['at'] ?? '', 11, 5) : null,
            returnArrivalDate: $returnItinerary ? substr(end($returnItinerary['segments'])['arrival']['at'] ?? '', 0, 10) : null,
            returnArrivalTime: $returnItinerary ? substr(end($returnItinerary['segments'])['arrival']['at'] ?? '', 11, 5) : null,
            returnDuration: $returnItinerary ? self::formatDuration($returnItinerary['duration'] ?? '') : null,
            returnStops: $returnItinerary ? max(0, count($returnItinerary['segments']) - 1) : null,
            isAmadeus: true,
            amadeusOfferId: $offer['id'] ?? null,
        );
    }

    public static function fromLocalFlight(\App\Models\Flight $flight): self
    {
        return new self(
            id: (string) $flight->id,
            source: 'local',
            airline: $flight->airline->code ?? '',
            airlineName: $flight->airline->name ?? '',
            flightNumber: $flight->flight_number,
            origin: $flight->fromAirport->code ?? '',
            originName: $flight->fromAirport->name ?? '',
            destination: $flight->toAirport->code ?? '',
            destinationName: $flight->toAirport->name ?? '',
            departureDate: $flight->departure_date?->format('Y-m-d') ?? '',
            departureTime: $flight->departure_time ?? '',
            arrivalDate: $flight->arrival_date?->format('Y-m-d') ?? '',
            arrivalTime: $flight->arrival_time ?? '',
            duration: '',
            stops: 0,
            priceTotal: (float) $flight->price_adult,
            currency: $flight->currency->code ?? 'USD',
            pricePerAdult: (float) $flight->price_adult,
            pricePerChild: $flight->price_child ? (float) $flight->price_child : null,
            pricePerInfant: $flight->price_infant ? (float) $flight->price_infant : null,
            availableSeats: $flight->available_seats ?? 0,
            cabinClass: $flight->class_type ?? 'economy',
            isAmadeus: false,
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    private static function formatDuration(string $isoDuration): string
    {
        if (empty($isoDuration)) return '';

        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $isoDuration, $matches);
        $hours = (int) ($matches[1] ?? 0);
        $minutes = (int) ($matches[2] ?? 0);

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }
}
