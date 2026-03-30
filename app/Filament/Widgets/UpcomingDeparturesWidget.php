<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FlightPaths\FlightPathResource;
use App\Models\FlightPath;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingDeparturesWidget extends TableWidget
{
    protected static ?string $heading = 'Upcoming Departures (Next 14 Days)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FlightPath::query()
                    ->where('is_available', true)
                    ->whereBetween('departure_date', [now(), now()->addDays(14)])
                    ->with(['stays.city', 'departureCity', 'legs.flight.airline'])
                    ->orderBy('departure_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('departure_date')
                    ->label('Departure')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (FlightPath $record) => $record->departure_date->isToday() ? 'danger' : ($record->departure_date->diffInDays(now()) <= 3 ? 'warning' : null)),

                TextColumn::make('route_name')
                    ->label('Route')
                    ->searchable(),

                TextColumn::make('stays_summary')
                    ->label('Cities')
                    ->formatStateUsing(function ($record) {
                        return $record->stays
                            ->sortBy('stay_order')
                            ->map(fn ($s) => ($s->city->name_en ?? '?') . ' ' . $s->nights . 'n')
                            ->implode(' → ');
                    }),

                TextColumn::make('nights')
                    ->label('Nights')
                    ->alignCenter(),

                TextColumn::make('flight_total')
                    ->label('Flight Total')
                    ->money('USD'),
            ])
            ->recordUrl(fn (FlightPath $record) => FlightPathResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
