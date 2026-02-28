<?php

namespace App\Filament\Resources\TransportTypes\Pages;

use App\Filament\Resources\TransportTypes\TransportTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransportType extends EditRecord
{
    protected static string $resource = TransportTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
