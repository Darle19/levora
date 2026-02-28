<?php

namespace App\Filament\Resources\Tours\Schemas;

use App\Models\AdditionalService;
use App\Models\Flight;
use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tour Details')
                    ->schema([
                        Select::make('tour_type_id')
                            ->relationship('tourType', 'id')
                            ->required(),
                        Select::make('program_type_id')
                            ->relationship('programType', 'id')
                            ->required(),
                        Select::make('country_id')
                            ->relationship('country', 'id')
                            ->required(),
                        Select::make('resort_id')
                            ->relationship('resort', 'id'),
                        Select::make('hotel_id')
                            ->relationship('hotel', 'name')
                            ->helperText('Hotel must have a price set for auto-pricing to work'),
                        Select::make('transport_type_id')
                            ->relationship('transportType', 'id')
                            ->required(),
                        Select::make('departure_city_id')
                            ->relationship('departureCity', 'id')
                            ->required(),
                        TextInput::make('nights')
                            ->required()
                            ->numeric(),
                        Select::make('currency_id')
                            ->relationship('currency', 'id')
                            ->required(),
                        DatePicker::make('date_from')
                            ->required(),
                        DatePicker::make('date_to')
                            ->required(),
                        TextInput::make('adults')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('children')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Select::make('meal_type_id')
                            ->relationship('mealType', 'id'),
                    ])
                    ->columns(2),

                Section::make('Flights')
                    ->schema([
                        Select::make('outbound_flight_ids')
                            ->label('Outbound Flight(s)')
                            ->options(fn() => Flight::where('is_active', true)
                                ->with(['fromAirport', 'toAirport', 'currency'])
                                ->get()
                                ->mapWithKeys(fn($f) => [
                                    $f->id => "{$f->flight_number} ({$f->fromAirport->code} → {$f->toAirport->code}) {$f->departure_date?->format('d.m.Y')} — {$f->price_adult} {$f->currency->code}",
                                ]))
                            ->multiple()
                            ->searchable(),
                        Select::make('return_flight_ids')
                            ->label('Return Flight(s)')
                            ->options(fn() => Flight::where('is_active', true)
                                ->with(['fromAirport', 'toAirport', 'currency'])
                                ->get()
                                ->mapWithKeys(fn($f) => [
                                    $f->id => "{$f->flight_number} ({$f->fromAirport->code} → {$f->toAirport->code}) {$f->departure_date?->format('d.m.Y')} — {$f->price_adult} {$f->currency->code}",
                                ]))
                            ->multiple()
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('markup_percent')
                            ->label('Markup Override (%)')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('Leave empty for global default')
                            ->helperText(fn() => 'Global default: ' . Setting::getValue('tour_markup_percent', 15) . '%'),
                        TextInput::make('price')
                            ->label('Computed Price (per person)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(true)
                            ->helperText('Auto-calculated: Hotel + Flights + Markup'),
                    ])
                    ->columns(2),

                Section::make('Additional Services')
                    ->schema([
                        CheckboxList::make('additional_service_ids')
                            ->label('Link Services to Tour')
                            ->options(fn () => AdditionalService::where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->name_en} ({$s->service_type}) — {$s->price} {$s->currency?->code}",
                                ]))
                            ->columns(2),
                    ]),

                Section::make('Options')
                    ->schema([
                        Toggle::make('is_available')
                            ->required(),
                        Toggle::make('is_hot')
                            ->required(),
                        Toggle::make('instant_confirmation')
                            ->required(),
                        Toggle::make('no_stop_sale')
                            ->required(),
                    ])
                    ->columns(4),
            ]);
    }
}
