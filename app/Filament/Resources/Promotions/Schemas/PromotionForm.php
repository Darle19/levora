<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('discount_percent')
                    ->numeric(),
                TextInput::make('discount_amount')
                    ->numeric(),
                Select::make('currency_id')
                    ->relationship('currency', 'code'),
                DatePicker::make('date_from')
                    ->required(),
                DatePicker::make('date_to')
                    ->required(),
                FileUpload::make('image')
                    ->image(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_hot')
                    ->required(),
                Textarea::make('target_countries')
                    ->columnSpanFull(),
            ]);
    }
}
