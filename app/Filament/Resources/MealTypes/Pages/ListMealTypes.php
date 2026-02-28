<?php

namespace App\Filament\Resources\MealTypes\Pages;

use App\Filament\Resources\MealTypes\MealTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMealTypes extends ListRecords
{
    protected static string $resource = MealTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
