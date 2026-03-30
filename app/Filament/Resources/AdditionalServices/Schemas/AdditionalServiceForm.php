<?php

namespace App\Filament\Resources\AdditionalServices\Schemas;

use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdditionalServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('city_id')
                            ->label('City')
                            ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('name_en')
                            ->label('Name (EN)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_ru')
                            ->label('Name (RU)')
                            ->maxLength(255),
                        TextInput::make('name_uz')
                            ->label('Name (UZ)')
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Price ($)')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(0),
                        Toggle::make('is_mandatory')
                            ->label('Mandatory')
                            ->helperText('Included in base tour price')
                            ->default(false),
                        Toggle::make('is_per_person')
                            ->label('Per Person')
                            ->helperText('Price multiplied by number of tourists')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }
}
