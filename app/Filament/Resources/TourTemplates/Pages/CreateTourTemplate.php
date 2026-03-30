<?php

namespace App\Filament\Resources\TourTemplates\Pages;

use App\Filament\Resources\TourTemplates\TourTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTourTemplate extends CreateRecord
{
    protected static string $resource = TourTemplateResource::class;

    protected function afterCreate(): void
    {
        // Calculate total_nights from stays
        $this->record->update([
            'total_nights' => $this->record->stays()->sum('nights'),
        ]);
    }
}
