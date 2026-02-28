<?php

namespace App\Filament\Resources\Resorts\Pages;

use App\Filament\Resources\Resorts\ResortResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResort extends EditRecord
{
    protected static string $resource = ResortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
