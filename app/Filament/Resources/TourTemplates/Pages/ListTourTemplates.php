<?php

namespace App\Filament\Resources\TourTemplates\Pages;

use App\Filament\Resources\TourTemplates\TourTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTourTemplates extends ListRecords
{
    protected static string $resource = TourTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
