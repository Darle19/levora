<?php

namespace App\Filament\Widgets;

use App\Models\FlightPath;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;

class TemplateFlightPathsTable extends TableWidget
{
    protected static ?string $heading = 'Generated Flight Paths';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isDiscovered = false;

    public Model|int|string|null $record = null;

    public function table(Table $table): Table
    {
        $templateId = $this->record instanceof Model ? $this->record->getKey() : $this->record;

        return $table
            ->query(
                FlightPath::query()
                    ->where('tour_template_id', $templateId ?? 0)
                    ->with([
                        'legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport',
                        'departureCity',
                    ])
            )
            ->columns([
                TextColumn::make('departure_date')
                    ->label('Departure')
                    ->date('d.m.Y (D)')
                    ->sortable(),
                TextColumn::make('flight_total')
                    ->label('Flight Total')
                    ->money('USD'),
                TextColumn::make('legs_summary')
                    ->label('Flights')
                    ->formatStateUsing(function ($record) {
                        return $record->legs->sortBy('leg_order')->map(function ($l) {
                            $f = $l->flight;
                            if (! $f) return '?';
                            return $f->fromAirport?->code . '→' . $f->toAirport?->code
                                . ' ' . ($f->airline?->code ?? '') . $f->flight_number
                                . ' $' . $f->price_adult;
                        })->implode(' | ');
                    }),
                IconColumn::make('is_available')
                    ->boolean(),
            ])
            ->defaultSort('departure_date')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
