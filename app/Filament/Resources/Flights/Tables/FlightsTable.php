<?php

namespace App\Filament\Resources\Flights\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlightsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('airline.name')
                    ->searchable(),
                TextColumn::make('fromAirport.id')
                    ->searchable(),
                TextColumn::make('toAirport.id')
                    ->searchable(),
                TextColumn::make('flight_number')
                    ->searchable(),
                TextColumn::make('departure_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('arrival_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('price_adult')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.id')
                    ->searchable(),
                TextColumn::make('available_seats')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('departure_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price_child')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_infant')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('arrival_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('class_type')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
