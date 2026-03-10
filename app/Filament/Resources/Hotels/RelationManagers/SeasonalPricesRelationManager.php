<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use App\Models\Currency;
use App\Models\MealType;
use App\Models\RoomType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeasonalPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'seasonalPrices';

    protected static ?string $title = 'Seasonal Room Prices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_type_id')
                    ->label('Room Type')
                    ->options(RoomType::where('is_active', true)->pluck('name_en', 'id'))
                    ->required()
                    ->searchable(),
                DatePicker::make('date_from')
                    ->label('From')
                    ->required(),
                DatePicker::make('date_to')
                    ->label('To')
                    ->required(),
                TextInput::make('price_single')
                    ->label('Single Room Price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('price_double')
                    ->label('Double Room Price')
                    ->numeric()
                    ->prefix('$'),
                Select::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::where('is_active', true)->pluck('code', 'id'))
                    ->required()
                    ->default(fn () => Currency::where('code', 'USD')->value('id')),
                Select::make('meal_type_id')
                    ->label('Meal Type')
                    ->options(MealType::where('is_active', true)->pluck('name_en', 'id'))
                    ->searchable(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('roomType.name_en')
                    ->label('Room Type')
                    ->sortable(),
                TextColumn::make('date_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('date_to')
                    ->date()
                    ->sortable(),
                TextColumn::make('price_single')
                    ->money()
                    ->label('Single'),
                TextColumn::make('price_double')
                    ->money()
                    ->label('Double'),
                TextColumn::make('currency.code')
                    ->label('Currency'),
                TextColumn::make('mealType.name_en')
                    ->label('Meal'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('date_from')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
