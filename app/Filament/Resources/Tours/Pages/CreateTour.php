<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use App\Models\TourAmadeusSegment;
use App\Services\TourPricingService;
use Filament\Resources\Pages\CreateRecord;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

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
