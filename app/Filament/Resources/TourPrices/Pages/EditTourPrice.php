<?php

namespace App\Filament\Resources\TourPrices\Pages;

use App\Filament\Resources\TourPrices\TourPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTourPrice extends EditRecord
{
    protected static string $resource = TourPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
