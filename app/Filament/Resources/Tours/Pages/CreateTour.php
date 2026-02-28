<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use App\Services\TourPricingService;
use Filament\Resources\Pages\CreateRecord;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        $outboundIds = $data['outbound_flight_ids'] ?? [];
        $returnIds = $data['return_flight_ids'] ?? [];

        foreach ($outboundIds as $flightId) {
            $record->flights()->attach($flightId, ['direction' => 'outbound']);
        }
        foreach ($returnIds as $flightId) {
            $record->flights()->attach($flightId, ['direction' => 'return']);
        }

        // Sync additional services
        $serviceIds = $data['additional_service_ids'] ?? [];
        $record->additionalServices()->sync($serviceIds);

        app(TourPricingService::class)->recalculate($record);
    }
}
