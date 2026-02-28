<?php

namespace App\Filament\Resources\TourPrices\Pages;

use App\Filament\Resources\TourPrices\TourPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTourPrices extends ListRecords
{
    protected static string $resource = TourPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
