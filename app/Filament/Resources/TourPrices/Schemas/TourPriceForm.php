<?php

namespace App\Filament\Resources\TourPrices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TourPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tour_id')
                    ->relationship('tour', 'id')
                    ->searchable()
                    ->required(),
                Select::make('room_type_id')
                    ->relationship('roomType', 'name_en')
                    ->searchable()
                    ->required(),
                TextInput::make('price_adult')
                    ->required()
                    ->numeric(),
                TextInput::make('price_child')
                    ->numeric(),
                TextInput::make('price_infant')
                    ->numeric(),
                Select::make('currency_id')
                    ->relationship('currency', 'code')
                    ->required(),
                TextInput::make('availability')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
