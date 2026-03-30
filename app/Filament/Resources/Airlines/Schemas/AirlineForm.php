<?php

namespace App\Filament\Resources\Airlines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AirlineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('baggage_fee')
                    ->label('Baggage Fee ($)')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->helperText('Per flight segment, added to fare'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
