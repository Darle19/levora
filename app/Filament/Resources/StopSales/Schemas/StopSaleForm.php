<?php

namespace App\Filament\Resources\StopSales\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StopSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('hotel_id')
                    ->relationship('hotel', 'name')
                    ->required(),
                DatePicker::make('date_from')
                    ->required(),
                DatePicker::make('date_to')
                    ->required(),
                Textarea::make('reason')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
