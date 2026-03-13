<?php

namespace App\Filament\Resources\Flights\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FlightForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('airline_id')
                    ->relationship('airline', 'name')
                    ->required(),
                Select::make('from_airport_id')
                    ->relationship('fromAirport', 'code')
                    ->searchable()
                    ->required(),
                Select::make('to_airport_id')
                    ->relationship('toAirport', 'code')
                    ->searchable()
                    ->required(),
                TextInput::make('flight_number')
                    ->required(),
                TimePicker::make('departure_time')
                    ->required(),
                TimePicker::make('arrival_time')
                    ->required(),
                TextInput::make('price_adult')
                    ->required()
                    ->numeric(),
                Select::make('currency_id')
                    ->relationship('currency', 'code')
                    ->required(),
                TextInput::make('available_seats')
                    ->required()
                    ->numeric(),
                DatePicker::make('departure_date')
                    ->required(),
                TextInput::make('price_child')
                    ->numeric(),
                TextInput::make('price_infant')
                    ->numeric(),
                DatePicker::make('arrival_date'),
                TextInput::make('class_type')
                    ->required()
                    ->default('economy'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
