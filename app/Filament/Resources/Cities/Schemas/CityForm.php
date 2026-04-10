<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
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
                Select::make('country_id')
                    ->relationship('country', 'name_en')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_departure')
                    ->label('Departure City')
                    ->helperText('Show in tour search departure filter'),
                TextInput::make('agent_phone')
                    ->label('Local Agent Phone')
                    ->placeholder('+998919777735'),
                TextInput::make('agent_name')
                    ->label('Local Agent Name')
                    ->placeholder('Принимающая компания'),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
