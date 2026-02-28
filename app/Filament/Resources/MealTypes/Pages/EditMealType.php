<?php

namespace App\Filament\Resources\MealTypes\Pages;

use App\Filament\Resources\MealTypes\MealTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMealType extends EditRecord
{
    protected static string $resource = MealTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
