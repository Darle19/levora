<?php

namespace App\Filament\Resources\Hotels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('address'),
                Select::make('resort_id')
                    ->relationship('resort', 'name_en')
                    ->required(),
                Select::make('hotel_category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('rating')
                    ->numeric(),
                Textarea::make('images')
                    ->columnSpanFull(),
                Textarea::make('amenities')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('name_en'),
                TextInput::make('name_ru'),
                TextInput::make('name_uz'),
                Textarea::make('description_en')
                    ->columnSpanFull(),
                Textarea::make('description_ru')
                    ->columnSpanFull(),
                Textarea::make('description_uz')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('website')
                    ->url(),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price_per_person')
                            ->label('Price Per Person')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->default(1),
                    ])
                    ->columns(2),
            ]);
    }
}
