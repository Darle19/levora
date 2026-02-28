<?php

namespace App\Filament\Resources\TourTypes\Pages;

use App\Filament\Resources\TourTypes\TourTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTourTypes extends ListRecords
{
    protected static string $resource = TourTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
