<?php

namespace App\Filament\Resources\Tours\Schemas;

use App\Models\AdditionalService;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Resort;
use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                            ->relationship('tourType', 'name_en')
                            ->required(),
                        Select::make('program_type_id')
                            ->relationship('programType', 'name_en')
                            ->required(),
                        Select::make('country_id')
                            ->relationship('country', 'name_en')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('resort_id', null);
                                $set('hotel_id', null);
                            }),
                        Select::make('resort_id')
                            ->label('Resort')
                            ->options(fn (Get $get) => Resort::query()
                                ->when($get('country_id'), fn ($q, $id) => $q->where('country_id', $id))
                                ->where('is_active', true)
                                ->whereNotNull('name_en')
                                ->pluck('name_en', 'id')
                                ->filter())
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('hotel_id', null)),
                        Select::make('hotel_id')
                            ->label('Hotel')
                            ->options(function (Get $get) {
                                $query = Hotel::where('is_active', true)->whereNotNull('name_en');
                                if ($get('resort_id')) {
                                    $query->where('resort_id', $get('resort_id'));
                                } elseif ($get('country_id')) {
                                    $query->whereHas('resort', fn ($q) => $q->where('country_id', $get('country_id')));
                                }

                                return $query->pluck('name_en', 'id')->filter();
                            })
                            ->searchable()
                            ->helperText('Hotel must have a price set for auto-pricing to work'),
                        Select::make('transport_type_id')
                            ->relationship('transportType', 'name_en')
                            ->required(),
                        Select::make('departure_city_id')
                            ->relationship('departureCity', 'name_en')
                            ->required()
                            ->live(),
                        TextInput::make('nights')
                            ->required()
                            ->numeric(),
                        Select::make('currency_id')
                            ->relationship('currency', 'code')
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
                            ->relationship('mealType', 'name_en'),
                    ])
                    ->columns(2),

                Section::make('Tour Route')
                    ->schema([
                        Repeater::make('tour_legs')
                            ->label('Flight Legs (in order)')
                            ->schema([
                                TextInput::make('leg_order')
                                    ->label('#')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                                Select::make('leg_type')
                                    ->label('Type')
                                    ->options([
                                        'local' => 'Local Flight (pre-bought)',
                                        'amadeus' => 'Amadeus Segment (on-demand)',
                                    ])
                                    ->required()
                                    ->live(),
                                Select::make('flight_id')
                                    ->label('Flight')
                                    ->options(function (Get $get) {
                                        return Flight::where('is_active', true)
                                            ->with(['fromAirport', 'toAirport', 'currency'])
                                            ->get()
                                            ->mapWithKeys(fn ($f) => [
                                                $f->id => "{$f->flight_number} ({$f->fromAirport->code} → {$f->toAirport->code}) {$f->departure_date?->format('d.m.Y')} — {$f->price_adult} {$f->currency->code}",
                                            ]);
                                    })
                                    ->searchable()
                                    ->visible(fn (Get $get) => $get('leg_type') === 'local'),
                                Select::make('direction')
                                    ->label('Direction')
                                    ->options([
                                        'outbound' => 'Outbound',
                                        'return' => 'Return',
                                    ])
                                    ->default('outbound')
                                    ->visible(fn (Get $get) => $get('leg_type') === 'local'),
                                Select::make('origin_airport_id')
                                    ->label('From Airport')
                                    ->options(fn () => Airport::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($a) => [$a->id => "{$a->code} — {$a->name_en}"]))
                                    ->searchable()
                                    ->visible(fn (Get $get) => $get('leg_type') === 'amadeus'),
                                Select::make('destination_airport_id')
                                    ->label('To Airport')
                                    ->options(fn () => Airport::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($a) => [$a->id => "{$a->code} — {$a->name_en}"]))
                                    ->searchable()
                                    ->visible(fn (Get $get) => $get('leg_type') === 'amadeus'),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('markup_percent')
                            ->label('Markup Override (%)')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('Leave empty for global default')
                            ->helperText(fn () => 'Global default: '.Setting::getValue('tour_markup_percent', 15).'%'),
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
