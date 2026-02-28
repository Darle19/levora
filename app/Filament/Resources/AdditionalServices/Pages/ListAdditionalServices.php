<?php

namespace App\Filament\Resources\AdditionalServices\Pages;

use App\Filament\Resources\AdditionalServices\AdditionalServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdditionalServices extends ListRecords
{
    protected static string $resource = AdditionalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
