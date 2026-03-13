<?php

namespace App\Filament\Resources\Airports\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AirportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('name_ru')
                    ->required(),
                TextInput::make('name_uz')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                Select::make('city_id')
                    ->relationship('city', 'name_en')
                    ->searchable()
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
