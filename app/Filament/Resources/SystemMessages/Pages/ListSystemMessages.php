<?php

namespace App\Filament\Resources\SystemMessages\Pages;

use App\Filament\Resources\SystemMessages\SystemMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSystemMessages extends ListRecords
{
    protected static string $resource = SystemMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
