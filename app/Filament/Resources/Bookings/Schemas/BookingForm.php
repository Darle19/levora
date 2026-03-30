<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Booking;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->required(),
                TextInput::make('bookable_type')
                    ->required(),
                TextInput::make('bookable_id')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->required()
                    ->default('pending'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('currency_id')
                    ->relationship('currency', 'code')
                    ->required(),
                DatePicker::make('date')
                    ->required(),

                Section::make('Amadeus Flight Selections')
                    ->schema([
                        Placeholder::make('amadeus_flights_info')
                            ->label('')
                            ->content(function (?Booking $record) {
                                if (! $record || $record->amadeusFlights->isEmpty()) {
                                    return 'No Amadeus flights selected.';
                                }

                                $lines = [];
                                foreach ($record->amadeusFlights as $af) {
                                    $lines[] = "<div style='margin-bottom:8px;padding:6px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:4px;'>"
                                        ."<strong>{$af->airline_name}</strong> {$af->flight_number} &nbsp; "
                                        ."{$af->origin} → {$af->destination} &nbsp; "
                                        ."{$af->departure_date?->format('d.m.Y')} {$af->departure_time}—{$af->arrival_time} &nbsp; "
                                        ."<strong>{$af->price_total} {$af->currency}</strong>"
                                        .'</div>';
                                }

                                return new \Illuminate\Support\HtmlString(implode('', $lines));
                            }),
                    ])
                    ->visible(fn (?Booking $record) => $record && $record->amadeusFlights->isNotEmpty())
                    ->collapsible(),
            ]);
    }
}
