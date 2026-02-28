<?php

namespace App\Filament\Resources\Tours\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class ToursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'tourType', 'programType', 'country', 'resort', 'hotel',
                'transportType', 'departureCity', 'currency', 'mealType',
            ]))
            ->columns([
                TextColumn::make('tourType.name_en')
                    ->label('Tour Type')
                    ->searchable(),
                TextColumn::make('programType.name_en')
                    ->label('Program')
                    ->searchable(),
                TextColumn::make('country.name_en')
                    ->label('Country')
                    ->searchable(),
                TextColumn::make('resort.name_en')
                    ->label('Resort')
                    ->searchable(),
                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable(),
                TextColumn::make('transportType.name_en')
                    ->label('Transport')
                    ->searchable(),
                TextColumn::make('departureCity.name_en')
                    ->label('Departure')
                    ->searchable(),
                TextColumn::make('nights')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency.code')
                    ->label('Currency')
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
                TextColumn::make('mealType.code')
                    ->label('Meal')
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
