<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use App\Models\TourAmadeusSegment;
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

        // Build tour_legs from local flights + amadeus segments, ordered by leg_order
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

        // Clear existing legs
        $record->flights()->detach();
        $record->amadeusSegments()->delete();

        // Re-sync from repeater
        $this->syncTourLegs($record, $data['tour_legs'] ?? []);

        // Sync additional services
        $serviceIds = $data['additional_service_ids'] ?? [];
        $record->additionalServices()->sync($serviceIds);

        app(TourPricingService::class)->recalculate($record);
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
