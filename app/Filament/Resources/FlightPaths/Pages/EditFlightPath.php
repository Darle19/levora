<?php

namespace App\Filament\Resources\FlightPaths\Pages;

use App\Filament\Resources\FlightPaths\FlightPathResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFlightPath extends EditRecord
{
    protected static string $resource = FlightPathResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
