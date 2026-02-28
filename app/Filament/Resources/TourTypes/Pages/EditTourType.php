<?php

namespace App\Filament\Resources\TourTypes\Pages;

use App\Filament\Resources\TourTypes\TourTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTourType extends EditRecord
{
    protected static string $resource = TourTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
