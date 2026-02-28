<?php

namespace App\Filament\Resources\HotelAmenityTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HotelAmenityTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('name_ru'),
                TextInput::make('name_uz'),
                TextInput::make('icon'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
