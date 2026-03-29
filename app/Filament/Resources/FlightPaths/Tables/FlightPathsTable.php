<?php

namespace App\Filament\Resources\FlightPaths\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class FlightPathsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport',
                'stays.city', 'departureCity', 'currency',
            ]))
            ->columns([
                TextColumn::make('route_name')
                    ->label('Route')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('departure_date')
                    ->label('Departure')
                    ->date('d.m.Y (D)')
                    ->sortable(),
                TextColumn::make('departureCity.name_en')
                    ->label('From'),
                TextColumn::make('nights')
                    ->label('Nights')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Flight Total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('legs')
                    ->label('Route Code')
                    ->formatStateUsing(function ($record) {
                        return $record->legs
                            ->sortBy('leg_order')
                            ->map(fn ($l) => $l->flight?->fromAirport?->code)
                            ->push($record->legs->sortByDesc('leg_order')->first()?->flight?->toAirport?->code)
                            ->filter()
                            ->unique()
                            ->implode('→');
                    }),
                TextColumn::make('legs_count')
                    ->label('Legs')
                    ->counts('legs')
                    ->sortable(),
                TextColumn::make('stays_summary')
                    ->label('Stays')
                    ->formatStateUsing(function ($record) {
                        return $record->stays
                            ->sortBy('stay_order')
                            ->map(fn ($s) => ($s->city->name_en ?? '?') . ' ' . $s->nights . 'n')
                            ->implode(' + ');
                    }),
                IconColumn::make('is_available')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('departure_date')
            ->filters([
                SelectFilter::make('route_name')
                    ->label('Route')
                    ->options(fn () => \App\Models\FlightPath::distinct()->pluck('route_name', 'route_name')->toArray()),
                TernaryFilter::make('is_available'),
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
