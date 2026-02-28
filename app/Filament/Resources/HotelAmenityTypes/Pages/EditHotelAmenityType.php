<?php

namespace App\Filament\Resources\HotelAmenityTypes\Pages;

use App\Filament\Resources\HotelAmenityTypes\HotelAmenityTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHotelAmenityType extends EditRecord
{
    protected static string $resource = HotelAmenityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
