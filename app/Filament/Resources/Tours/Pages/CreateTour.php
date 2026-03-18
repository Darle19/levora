<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use App\Models\TourAmadeusSegment;
use App\Models\TourStay;
use App\Services\TourPricingService;
use Filament\Resources\Pages\CreateRecord;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        $this->syncTourStays($record, $data['tour_stays'] ?? []);
        $this->syncTourLegs($record, $data['tour_legs'] ?? []);

        // Update total nights from stays
        $totalNights = collect($data['tour_stays'] ?? [])->sum('nights');
        if ($totalNights > 0) {
            $record->updateQuietly(['nights' => $totalNights]);
        }

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
