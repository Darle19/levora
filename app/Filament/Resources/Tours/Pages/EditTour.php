<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use App\Models\TourAmadeusSegment;
use App\Models\TourStay;
use App\Services\TourPricingService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tour = $this->getRecord();

        // Build tour_stays from existing stays
        $data['tour_stays'] = $tour->stays->map(fn ($stay) => [
            'stay_order' => $stay->stay_order,
            'city_id' => $stay->city_id,
            'hotel_id' => $stay->hotel_id,
            'resort_id' => $stay->resort_id,
            'nights' => $stay->nights,
            'meal_type_id' => $stay->meal_type_id,
            'price_per_person' => $stay->price_per_person,
            'currency_id' => $stay->currency_id,
        ])->toArray();

        // Build tour_legs from local flights + amadeus segments
        $legs = [];

        foreach ($tour->flights as $flight) {
            $legs[] = [
                'leg_order' => $flight->pivot->leg_order ?? 1,
                'leg_type' => 'local',
                'flight_id' => $flight->id,
                'direction' => $flight->pivot->direction ?? 'outbound',
                'origin_airport_id' => null,
                'destination_airport_id' => null,
            ];
        }

        foreach ($tour->amadeusSegments as $segment) {
            $legs[] = [
                'leg_order' => $segment->leg_order,
                'leg_type' => 'amadeus',
                'flight_id' => null,
                'direction' => null,
                'origin_airport_id' => $segment->origin_airport_id,
                'destination_airport_id' => $segment->destination_airport_id,
            ];
        }

        usort($legs, fn ($a, $b) => ($a['leg_order'] ?? 0) <=> ($b['leg_order'] ?? 0));
        $data['tour_legs'] = $legs;

        $data['additional_service_ids'] = $tour->additionalServices()
            ->pluck('additional_services.id')
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $data = $this->form->getState();

        // Clear and re-sync stays
        $record->stays()->delete();
        $this->syncTourStays($record, $data['tour_stays'] ?? []);

        // Update total nights from stays
        $totalNights = collect($data['tour_stays'] ?? [])->sum('nights');
        if ($totalNights > 0) {
            $record->updateQuietly(['nights' => $totalNights]);
        }

        // Clear and re-sync legs
        $record->flights()->detach();
        $record->amadeusSegments()->delete();
        $this->syncTourLegs($record, $data['tour_legs'] ?? []);

        // Sync additional services
        $serviceIds = $data['additional_service_ids'] ?? [];
        $record->additionalServices()->sync($serviceIds);

        app(TourPricingService::class)->recalculate($record);
    }

    private function syncTourStays($tour, array $stays): void
    {
        foreach ($stays as $stay) {
            if (empty($stay['nights'])) {
                continue;
            }
            TourStay::create([
                'tour_id' => $tour->id,
                'stay_order' => $stay['stay_order'] ?? 1,
                'city_id' => $stay['city_id'] ?? null,
                'hotel_id' => $stay['hotel_id'] ?? null,
                'resort_id' => $stay['resort_id'] ?? null,
                'nights' => $stay['nights'],
                'meal_type_id' => $stay['meal_type_id'] ?? null,
                'price_per_person' => $stay['price_per_person'] ?? null,
                'currency_id' => $stay['currency_id'] ?? null,
            ]);
        }
    }

    private function syncTourLegs($tour, array $legs): void
    {
        foreach ($legs as $leg) {
            if (($leg['leg_type'] ?? '') === 'local' && ! empty($leg['flight_id'])) {
                $tour->flights()->attach($leg['flight_id'], [
                    'direction' => $leg['direction'] ?? 'outbound',
                    'leg_order' => $leg['leg_order'] ?? 1,
                ]);
            } elseif (($leg['leg_type'] ?? '') === 'amadeus' && ! empty($leg['origin_airport_id']) && ! empty($leg['destination_airport_id'])) {
                TourAmadeusSegment::create([
                    'tour_id' => $tour->id,
                    'leg_order' => $leg['leg_order'] ?? 1,
                    'origin_airport_id' => $leg['origin_airport_id'],
                    'destination_airport_id' => $leg['destination_airport_id'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
