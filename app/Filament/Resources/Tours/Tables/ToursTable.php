<?php

namespace App\Filament\Resources\Tours\Tables;

use App\Services\TourPricingService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
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
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Table;

class ToursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'programType', 'country', 'resort', 'hotel',
                'transportType', 'departureCity', 'currency', 'mealType',
            ]))
            ->columns([
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
                SelectFilter::make('country_id')
                    ->relationship('country', 'name_en')
                    ->label('Country'),
                TernaryFilter::make('is_available'),
                TernaryFilter::make('is_hot'),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from'),
                        DatePicker::make('date_to'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['date_from'], fn ($q, $d) => $q->where('date_from', '>=', $d))
                        ->when($data['date_to'], fn ($q, $d) => $q->where('date_from', '<=', $d))
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make(),
                Action::make('toggle_availability')
                    ->label('Toggle Availability')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_available' => ! $record->is_available]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_available' => true])),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_available' => false])),
                    BulkAction::make('recalculate_prices')
                        ->label('Recalculate Prices')
                        ->icon('heroicon-o-calculator')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $service = app(TourPricingService::class);
                            $records->each(fn ($tour) => $service->recalculate($tour));
                        }),
                ]),
            ]);
    }
}
