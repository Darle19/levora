<?php

namespace App\Filament\Resources\TransportTypes\Pages;

use App\Filament\Resources\TransportTypes\TransportTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransportTypes extends ListRecords
{
    protected static string $resource = TransportTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
