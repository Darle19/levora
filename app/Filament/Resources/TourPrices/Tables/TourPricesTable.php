<?php

namespace App\Filament\Resources\TourPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class TourPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'tour.hotel', 'roomType', 'currency',
            ]))
            ->columns([
                TextColumn::make('tour.hotel.name')
                    ->label('Tour / Hotel')
                    ->searchable(),
                TextColumn::make('roomType.name_en')
                    ->label('Room Type')
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
                TextColumn::make('currency.code')
                    ->label('Currency')
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
