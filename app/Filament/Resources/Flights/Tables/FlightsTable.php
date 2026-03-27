<?php

namespace App\Filament\Resources\Flights\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class FlightsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'airline', 'fromAirport', 'toAirport', 'currency',
            ]))
            ->columns([
                TextColumn::make('airline.name')
                    ->searchable(),
                TextColumn::make('fromAirport.code')
                    ->label('From')
                    ->searchable(),
                TextColumn::make('toAirport.code')
                    ->label('To')
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
                TextColumn::make('currency.code')
                    ->label('Currency')
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
                SelectFilter::make('airline_id')
                    ->relationship('airline', 'name')
                    ->label('Airline'),
                SelectFilter::make('from_airport_id')
                    ->relationship('fromAirport', 'code')
                    ->label('From Airport'),
                SelectFilter::make('to_airport_id')
                    ->relationship('toAirport', 'code')
                    ->label('To Airport'),
                TernaryFilter::make('is_active'),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from'),
                        DatePicker::make('date_to'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['date_from'], fn ($q, $d) => $q->where('departure_date', '>=', $d))
                        ->when($data['date_to'], fn ($q, $d) => $q->where('departure_date', '<=', $d))
                    ),
                Filter::make('low_seats')
                    ->label('Low Seats (< 5)')
                    ->query(fn (Builder $query) => $query->where('available_seats', '<', 5)),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make(),
                Action::make('toggle_active')
                    ->label('Toggle Active')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => ! $record->is_active]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
