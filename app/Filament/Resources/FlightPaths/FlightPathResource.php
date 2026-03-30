<?php

namespace App\Filament\Resources\FlightPaths;

use App\Filament\Resources\FlightPaths\Pages\ListFlightPaths;
use App\Filament\Resources\FlightPaths\Pages\EditFlightPath;
use App\Filament\Resources\FlightPaths\Tables\FlightPathsTable;
use App\Models\City;
use App\Models\Currency;
use App\Models\FlightPath;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FlightPathResource extends Resource
{
    protected static ?string $model = FlightPath::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Tours';

    protected static ?string $slug = 'tours';

    protected static ?string $pluralModelLabel = 'Tours';

    protected static ?string $modelLabel = 'Tour';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Route')
                ->schema([
                    TextInput::make('route_name')
                        ->label('Route Name')
                        ->required(),
                    DatePicker::make('departure_date')
                        ->label('Departure Date')
                        ->required(),
                    Select::make('departure_city_id')
                        ->label('Departure City')
                        ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                        ->required(),
                    TextInput::make('nights')
                        ->label('Nights')
                        ->numeric()
                        ->required(),
                    TextInput::make('total_price')
                        ->label('Flight Total ($)')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Select::make('currency_id')
                        ->label('Currency')
                        ->options(Currency::where('is_active', true)->pluck('code', 'id'))
                        ->required(),
                    Toggle::make('is_available')
                        ->label('Available')
                        ->default(true),
                ])
                ->columns(3),

            Section::make('Flight Legs')
                ->schema([
                    Placeholder::make('legs_info')
                        ->content(function (?FlightPath $record) {
                            if (! $record) { return 'Save first to see legs.'; }
                            $record->load('legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport');
                            return $record->legs->sortBy('leg_order')->map(function ($l) {
                                $f = $l->flight;
                                return "Leg {$l->leg_order}: {$f->fromAirport->code}→{$f->toAirport->code} | {$f->airline->code} {$f->flight_number} | {$f->departure_date->format('d.m')} {$f->departure_time}—{$f->arrival_time} | \${$f->price_adult} | {$l->direction}";
                            })->implode("\n");
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('Stays')
                ->schema([
                    Placeholder::make('stays_info')
                        ->content(function (?FlightPath $record) {
                            if (! $record) { return 'Save first to see stays.'; }
                            $record->load('stays.city');
                            return $record->stays->sortBy('stay_order')->map(function ($s) {
                                return "{$s->stay_order}. {$s->city->name_en} — {$s->nights} nights";
                            })->implode("\n");
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return FlightPathsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlightPaths::route('/'),
            'edit' => EditFlightPath::route('/{record}/edit'),
        ];
    }
}
