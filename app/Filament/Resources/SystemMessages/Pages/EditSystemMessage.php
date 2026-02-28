<?php

namespace App\Filament\Resources\SystemMessages\Pages;

use App\Filament\Resources\SystemMessages\SystemMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSystemMessage extends EditRecord
{
    protected static string $resource = SystemMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
