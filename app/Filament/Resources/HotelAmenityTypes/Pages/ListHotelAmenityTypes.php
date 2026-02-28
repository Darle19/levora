<?php

namespace App\Filament\Resources\HotelAmenityTypes\Pages;

use App\Filament\Resources\HotelAmenityTypes\HotelAmenityTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHotelAmenityTypes extends ListRecords
{
    protected static string $resource = HotelAmenityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
