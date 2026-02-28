<?php

namespace App\Filament\Resources\HotelCategories\Pages;

use App\Filament\Resources\HotelCategories\HotelCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHotelCategories extends ListRecords
{
    protected static string $resource = HotelCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
