<?php

namespace App\Filament\Resources\CurrencyRates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurrencyRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_currency_id')
                    ->relationship('fromCurrency', 'code')
                    ->required(),
                Select::make('to_currency_id')
                    ->relationship('toCurrency', 'code')
                    ->required(),
                TextInput::make('rate')
                    ->required()
                    ->numeric(),
                DatePicker::make('date')
                    ->required(),
            ]);
    }
}
