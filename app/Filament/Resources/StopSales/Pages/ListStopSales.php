<?php

namespace App\Filament\Resources\StopSales\Pages;

use App\Filament\Resources\StopSales\StopSaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStopSales extends ListRecords
{
    protected static string $resource = StopSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
