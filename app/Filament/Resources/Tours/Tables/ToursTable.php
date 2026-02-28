<?php

namespace App\Filament\Resources\Tours\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ToursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tourType.id')
                    ->searchable(),
                TextColumn::make('programType.id')
                    ->searchable(),
                TextColumn::make('country.id')
                    ->searchable(),
                TextColumn::make('resort.id')
                    ->searchable(),
                TextColumn::make('hotel.name')
                    ->searchable(),
                TextColumn::make('transportType.id')
                    ->searchable(),
                TextColumn::make('departureCity.id')
                    ->searchable(),
                TextColumn::make('nights')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency.id')
                    ->searchable(),
                TextColumn::make('date_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('date_to')
                    ->date()
                    ->sortable(),
                TextColumn::make('adults')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('children')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('mealType.id')
                    ->searchable(),
                IconColumn::make('is_available')
                    ->boolean(),
                IconColumn::make('is_hot')
                    ->boolean(),
                IconColumn::make('instant_confirmation')
                    ->boolean(),
                IconColumn::make('no_stop_sale')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
