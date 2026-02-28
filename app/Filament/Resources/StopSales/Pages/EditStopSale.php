<?php

namespace App\Filament\Resources\StopSales\Pages;

use App\Filament\Resources\StopSales\StopSaleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStopSale extends EditRecord
{
    protected static string $resource = StopSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
