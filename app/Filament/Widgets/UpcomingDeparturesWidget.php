<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Tours\TourResource;
use App\Models\Tour;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingDeparturesWidget extends TableWidget
{
    protected static ?string $heading = 'Upcoming Departures (Next 14 Days)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tour::query()
                    ->where('is_available', true)
                    ->whereBetween('date_from', [now(), now()->addDays(14)])
                    ->with(['stays.city', 'stays.hotel', 'hotel'])
                    ->orderBy('date_from', 'asc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('date_from')
                    ->label('Departure')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (Tour $record) => $record->date_from->isToday() ? 'danger' : ($record->date_from->diffInDays(now()) <= 3 ? 'warning' : null)),

                TextColumn::make('route')
                    ->label('Route')
                    ->state(function (Tour $record): string {
                        if ($record->stays->isNotEmpty()) {
                            return $record->stays
                                ->map(fn ($stay) => $stay->city?->name ?? '—')
                                ->unique()
                                ->implode(' → ');
                        }

                        return $record->departureCity?->name ?? '—';
                    }),

                TextColumn::make('hotel_name')
                    ->label('Hotel')
                    ->state(function (Tour $record): string {
                        if ($record->stays->isNotEmpty()) {
                            return $record->stays
                                ->map(fn ($stay) => $stay->hotel?->name ?? '—')
                                ->unique()
                                ->implode(', ');
                        }

                        return $record->hotel?->name ?? '—';
                    }),

                TextColumn::make('nights')
                    ->label('Nights')
                    ->alignCenter(),

                TextColumn::make('adults')
                    ->label('Pax')
                    ->alignCenter()
                    ->state(fn (Tour $record) => $record->adults . ($record->children > 0 ? '+' . $record->children : '')),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
            ])
            ->recordUrl(fn (Tour $record) => TourResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
