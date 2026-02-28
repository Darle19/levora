<?php

namespace App\Filament\Resources\HotelCategories\Pages;

use App\Filament\Resources\HotelCategories\HotelCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHotelCategory extends EditRecord
{
    protected static string $resource = HotelCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
