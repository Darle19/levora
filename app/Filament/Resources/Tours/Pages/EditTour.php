<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
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

        $data['outbound_flight_ids'] = $tour->flights()
            ->wherePivot('direction', 'outbound')
            ->pluck('flights.id')
            ->toArray();

        $data['return_flight_ids'] = $tour->flights()
            ->wherePivot('direction', 'return')
            ->pluck('flights.id')
            ->toArray();

        $data['additional_service_ids'] = $tour->additionalServices()
            ->pluck('additional_services.id')
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $data = $this->form->getState();

        $outboundIds = $data['outbound_flight_ids'] ?? [];
        $returnIds = $data['return_flight_ids'] ?? [];

        $record->flights()->detach();

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
