<?php

// File: app/Services/TourTemplateService.php

namespace App\Services;

use App\DTOs\FlightOffer;
use App\DTOs\TourTemplateSummary;
use App\Enums\TourTemplateStatus;
use App\Models\TourTemplate;
use App\Models\TourTemplateLeg;
use App\Models\TourTemplateFlightSelection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TourTemplateService
{
    public function __construct(
        private readonly FlightSearchService $flightSearch,
    ) {}

    // ── CRUD ──

    public function create(array $data): TourTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = TourTemplate::create([
                'route_name' => $data['route_name'],
                'departure_city_id' => $data['departure_city_id'],
                'status' => TourTemplateStatus::Draft,
                'base_currency' => $data['base_currency'] ?? config('tour.default_currency', 'USD'),
                'margin_percent' => $data['margin_percent'] ?? config('tour.default_margin_percent', 10),
                'is_active' => true,
                'total_nights' => 0,
            ]);

            if (! empty($data['stays'])) {
                $this->syncStays($template, $data['stays']);
            }

            if (! empty($data['legs'])) {
                $this->validateLegs($data['legs']);
                $this->syncLegs($template, $data['legs']);
            }

            $template->recalculateTotalNights();

            return $template->load('stays.city', 'legs.departureCity', 'legs.arrivalCity');
        });
    }

    public function update(TourTemplate $template, array $data): TourTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            $template->update(array_filter([
                'route_name' => $data['route_name'] ?? null,
                'departure_city_id' => $data['departure_city_id'] ?? null,
                'base_currency' => $data['base_currency'] ?? null,
                'margin_percent' => $data['margin_percent'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn ($v) => $v !== null));

            if (isset($data['stays'])) {
                $this->syncStays($template, $data['stays']);
                $template->recalculateTotalNights();
            }

            if (isset($data['legs'])) {
                $this->validateLegs($data['legs']);
                $this->syncLegs($template, $data['legs']);
            }

            return $template->fresh('stays.city', 'legs.departureCity', 'legs.arrivalCity');
        });
    }

    // ── Stays ──

    private function syncStays(TourTemplate $template, array $stays): void
    {
        $template->stays()->delete();

        foreach ($stays as $i => $stay) {
            $template->stays()->create([
                'city_id' => $stay['city_id'],
                'stay_order' => $i + 1,
                'nights' => $stay['nights'],
                'check_in_date' => $stay['check_in_date'] ?? null,
                'check_out_date' => $stay['check_out_date'] ?? null,
            ]);
        }
    }

    // ── Legs ──

    private function syncLegs(TourTemplate $template, array $legs): void
    {
        $template->legs()->delete(); // cascades flight selections too

        foreach ($legs as $i => $leg) {
            $template->legs()->create([
                'leg_order' => $i + 1,
                'departure_city_id' => $leg['departure_city_id'],
                'arrival_city_id' => $leg['arrival_city_id'],
                'departure_date' => $leg['departure_date'],
                'arrival_date' => $leg['arrival_date'] ?? $leg['departure_date'],
                'preferred_time_range' => $leg['preferred_time_range'] ?? 'any',
                'passenger_count' => $leg['passenger_count'] ?? 1,
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateLegs(array $legs): void
    {
        $errors = [];

        for ($i = 1; $i < count($legs); $i++) {
            $prevArrival = $legs[$i - 1]['arrival_date'] ?? $legs[$i - 1]['departure_date'];
            $currDeparture = $legs[$i]['departure_date'];

            if ($currDeparture < $prevArrival) {
                $errors["legs.{$i}.departure_date"] = [
                    "Leg " . ($i + 1) . " departs before leg {$i} arrives.",
                ];
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    // ── Flight Search ──

    /**
     * @return FlightOffer[]
     */
    public function searchFlightsForLeg(TourTemplateLeg $leg, string $sortBy = 'price'): array
    {
        return $this->flightSearch->searchForLeg($leg, $sortBy);
    }

    // ── Flight Selection ──

    public function selectFlight(TourTemplateLeg $leg, FlightOffer $offer): TourTemplateFlightSelection
    {
        // Replace any existing selection for this leg
        $leg->flightSelection()->delete();

        $selection = $leg->flightSelection()->create([
            'flight_id' => $offer->localFlightId,
            'provider_flight_id' => $offer->providerFlightId,
            'airline_code' => $offer->airlineCode,
            'flight_number' => $offer->flightNumber,
            'departure_datetime' => $offer->departureAt->format('Y-m-d H:i:s'),
            'arrival_datetime' => $offer->arrivalAt->format('Y-m-d H:i:s'),
            'price_cents' => $offer->priceCents,
            'currency' => $offer->currency,
            'seats_available' => $offer->seatsAvailable,
            'raw_data' => $offer->rawData,
            'selected_at' => now(),
        ]);

        // If all legs now have flights, lock the template
        $template = $leg->template;
        if ($template->allFlightsSelected()) {
            $template->update(['status' => TourTemplateStatus::FlightsLocked]);
        }

        return $selection;
    }

    // ── Summary ──

    public function summary(TourTemplate $template): TourTemplateSummary
    {
        $template->load('legs.departureCity', 'legs.arrivalCity', 'legs.flightSelection', 'stays.city');

        $legs = $template->legs->map(function (TourTemplateLeg $leg) {
            $sel = $leg->flightSelection;
            return [
                'leg_order' => $leg->leg_order,
                'from' => $leg->departureCity->name_en,
                'to' => $leg->arrivalCity->name_en,
                'date' => $leg->departure_date->format('Y-m-d'),
                'flight' => $sel ? "{$sel->airline_code}{$sel->flight_number}" : null,
                'price_cents' => $sel?->price_cents,
            ];
        })->all();

        $stays = $template->stays->map(fn ($s) => [
            'city' => $s->city->name_en,
            'nights' => $s->nights,
            'check_in' => $s->check_in_date?->format('Y-m-d') ?? '',
            'check_out' => $s->check_out_date?->format('Y-m-d') ?? '',
        ])->all();

        return new TourTemplateSummary(
            id: $template->id,
            routeName: $template->route_name,
            status: $template->status->value,
            baseCurrency: $template->base_currency,
            marginPercent: $template->margin_percent,
            totalNights: $template->total_nights,
            legs: $legs,
            stays: $stays,
            totalFlightCostCents: $template->totalFlightCostCents(),
            allFlightsSelected: $template->allFlightsSelected(),
        );
    }
}
