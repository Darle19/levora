<?php

namespace App\Filament\Resources\TourTemplates;

use App\Filament\Resources\TourTemplates\Pages\CreateTourTemplate;
use App\Filament\Resources\TourTemplates\Pages\EditTourTemplate;
use App\Filament\Resources\TourTemplates\Pages\ListTourTemplates;
use App\Models\AdditionalService;
use App\Models\Airline;
use App\Models\City;
use App\Models\TourTemplate;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class TourTemplateResource extends Resource
{
    protected static ?string $model = TourTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Tours';

    protected static ?string $slug = 'tours';

    protected static ?string $pluralModelLabel = 'Tour Templates';

    protected static ?string $modelLabel = 'Tour Template';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Route')
                ->schema([
                    TextInput::make('route_name')
                        ->label('Route Name')
                        ->placeholder('Istanbul + Nice')
                        ->required(),
                    Select::make('departure_city_id')
                        ->label('Departure City')
                        ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(3),

            Section::make('City Stays')
                ->description('Add cities in visit order. The system derives flight legs: Departure → City 1 → City 2 → ... → Departure.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('stays')
                        ->relationship()
                        ->schema([
                            Select::make('city_id')
                                ->label('City')
                                ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('nights')
                                ->label('Nights')
                                ->numeric()
                                ->default(2)
                                ->required()
                                ->columnSpan(1),
                            DatePicker::make('check_in_date')
                                ->label('Check-in')
                                ->columnSpan(1),
                            DatePicker::make('check_out_date')
                                ->label('Check-out')
                                ->columnSpan(1),
                            Repeater::make('services')
                                ->relationship()
                                ->schema([
                                    Select::make('additional_service_id')
                                        ->label('Service')
                                        ->options(AdditionalService::where('is_active', true)->pluck('name_en', 'id'))
                                        ->required()
                                        ->searchable(),
                                    TextInput::make('price_cents')
                                        ->label('Price (cents)')
                                        ->numeric()
                                        ->required()
                                        ->helperText('e.g. 3000 = $30.00'),
                                    Toggle::make('is_mandatory')
                                        ->label('Mandatory')
                                        ->default(false)
                                        ->helperText('Included in base price'),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel('+ Add Service')
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->defaultItems(2)
                        ->minItems(1)
                        ->maxItems(5)
                        ->reorderable()
                        ->orderColumn('stay_order')
                        ->addActionLabel('+ Add City'),
                ]),

            Section::make('Flight Legs')
                ->description('Define flight segments. Day offset = days from base departure date. Use "Generate Flights" to search and create paths.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('legs')
                        ->relationship()
                        ->schema([
                            Select::make('departure_city_id')
                                ->label('From')
                                ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                ->required(),
                            Select::make('arrival_city_id')
                                ->label('To')
                                ->options(City::where('is_active', true)->pluck('name_en', 'id'))
                                ->required(),
                            Select::make('airline_id')
                                ->label('Airline')
                                ->options(Airline::where('is_active', true)->pluck('name', 'id'))
                                ->placeholder('Any')
                                ->searchable(),
                            TextInput::make('day_offset')
                                ->label('Day +')
                                ->numeric()
                                ->default(0)
                                ->required(),
                            Select::make('flight_source')
                                ->label('Source')
                                ->options([
                                    'local_db' => 'Local DB',
                                    'rapidapi' => 'RapidAPI',
                                ])
                                ->default('local_db')
                                ->required(),
                        ])
                        ->columns(5)
                        ->defaultItems(0)
                        ->maxItems(10)
                        ->reorderable()
                        ->orderColumn('leg_order')
                        ->addActionLabel('+ Add Leg'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['stays.city', 'departureCity']))
            ->columns([
                TextColumn::make('route_name')
                    ->label('Route')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('departureCity.name_en')
                    ->label('Departure'),
                TextColumn::make('stays_summary')
                    ->label('Cities')
                    ->formatStateUsing(function ($record) {
                        return $record->stays
                            ->sortBy('stay_order')
                            ->map(fn ($s) => ($s->city->name_en ?? '?') . ' ' . $s->nights . 'n')
                            ->implode(' → ');
                    }),
                TextColumn::make('total_nights')
                    ->label('Nights')
                    ->sortable(),
                TextColumn::make('flight_paths_count')
                    ->label('Generated')
                    ->counts('flightPaths')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTourTemplates::route('/'),
            'create' => CreateTourTemplate::route('/create'),
            'edit' => EditTourTemplate::route('/{record}/edit'),
        ];
    }
}
