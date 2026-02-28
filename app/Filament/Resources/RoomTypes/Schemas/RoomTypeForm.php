<?php

namespace App\Filament\Resources\RoomTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('name_ru'),
                TextInput::make('name_uz'),
                TextInput::make('code')
                    ->required(),
                TextInput::make('max_adults')
                    ->required()
                    ->numeric()
                    ->default(2),
                TextInput::make('max_children')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
