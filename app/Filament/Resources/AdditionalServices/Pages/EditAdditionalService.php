<?php

namespace App\Filament\Resources\AdditionalServices\Pages;

use App\Filament\Resources\AdditionalServices\AdditionalServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdditionalService extends EditRecord
{
    protected static string $resource = AdditionalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
