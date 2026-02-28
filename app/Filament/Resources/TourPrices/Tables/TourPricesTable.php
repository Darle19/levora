<?php

namespace App\Filament\Resources\TourPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TourPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tour.id')
                    ->searchable(),
                TextColumn::make('roomType.id')
                    ->searchable(),
                TextColumn::make('price_adult')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_child')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_infant')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.id')
                    ->searchable(),
                TextColumn::make('availability')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
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
