<?php

namespace App\Filament\Resources\AdditionalServices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdditionalServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Select::make('service_type')
                            ->options([
                                'transfer' => 'Transfer',
                                'excursion' => 'Excursion',
                                'insurance' => 'Insurance',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('other'),
                    ])
                    ->columns(2),

                Section::make('Names')
                    ->schema([
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
                    ])
                    ->columns(3),

                Section::make('Descriptions')
                    ->schema([
                        Textarea::make('description_en')
                            ->label('Description (EN)')
                            ->rows(2),
                        Textarea::make('description_ru')
                            ->label('Description (RU)')
                            ->rows(2),
                        Textarea::make('description_uz')
                            ->label('Description (UZ)')
                            ->rows(2),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required()
                            ->default(0),
                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->required(),
                        Toggle::make('is_per_person')
                            ->label('Per Person')
                            ->helperText('If enabled, price is multiplied by the number of tourists')
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
