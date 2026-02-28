<?php

namespace App\Filament\Resources\AdditionalServices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdditionalServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'transfer' => 'info',
                        'excursion' => 'success',
                        'insurance' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->sortable(),
                IconColumn::make('is_per_person')
                    ->label('Per Person')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
