<?php

namespace App\Filament\Resources\Resorts\Pages;

use App\Filament\Resources\Resorts\ResortResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResorts extends ListRecords
{
    protected static string $resource = ResortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
