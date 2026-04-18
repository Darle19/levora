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
use App\Models\TourTemplate as TourTemplateModel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\HtmlString;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Jobs\GenerateFlightPathsJob;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;

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
                ->description('Define flight segments. Day offset = days from base departure date. Each leg can accept multiple airlines; the generator emits one FlightPath per valid combo.')
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
                            Select::make('airlines')
                                ->label('Airlines')
                                ->relationship('airlines', 'name')
                                ->options(Airline::where('is_active', true)->pluck('name', 'id'))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->helperText('Pick every carrier that may operate this leg'),
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
                            Select::make('round_trip_pair_id')
                                ->label('Pairs with leg')
                                ->helperText('Force same airline on two linked legs (block seats). Pick the peer leg.')
                                ->options(function ($livewire, $record) {
                                    // $record here is the sibling leg (TourTemplateLeg) in edit-mode;
                                    // fall back to the template's record on the Livewire page.
                                    $template = $livewire->record ?? null;
                                    if (! $template) {
                                        return [];
                                    }
                                    $legs = $template->legs()->orderBy('leg_order')->get();
                                    return $legs
                                        ->when($record, fn ($c) => $c->where('id', '!=', $record->id))
                                        ->mapWithKeys(fn ($l) => [$l->id => "Leg {$l->leg_order} ({$l->departureCity?->name_en} → {$l->arrivalCity?->name_en})"])
                                        ->all();
                                })
                                ->placeholder('No pair')
                                ->columnSpan(2),
                        ])
                        ->columns(6)
                        ->defaultItems(0)
                        ->maxItems(10)
                        ->reorderable()
                        ->orderColumn('leg_order')
                        ->addActionLabel('+ Add Leg'),
                ]),

            Section::make('Generated FlightPaths')
                ->description('Snapshot of paths emitted by the last generator run. Click "Generate Paths" on the list page to build more.')
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('flight_paths_summary')
                        ->label('')
                        ->content(function (?TourTemplateModel $record): HtmlString {
                            if (! $record) {
                                return new HtmlString('<em style="color:#999">Save the template first.</em>');
                            }

                            $paths = $record->flightPaths()
                                ->with(['legs.flight.airline', 'legs.flight.fromAirport', 'legs.flight.toAirport', 'currency'])
                                ->orderBy('departure_date')
                                ->orderBy('id')
                                ->get();

                            if ($paths->isEmpty()) {
                                return new HtmlString('<em style="color:#999">No FlightPaths generated yet.</em>');
                            }

                            $html = '<table style="width:100%;font-size:12px;border-collapse:collapse">';
                            $html .= '<thead><tr style="background:#f3f4f6;text-align:left">';
                            $html .= '<th style="padding:6px 8px">FP</th>';
                            $html .= '<th style="padding:6px 8px">Depart</th>';
                            $html .= '<th style="padding:6px 8px">Legs</th>';
                            $html .= '<th style="padding:6px 8px;text-align:right">Flight total</th>';
                            $html .= '</tr></thead><tbody>';

                            foreach ($paths as $fp) {
                                $legs = $fp->legs->sortBy('leg_order')->map(function ($l) {
                                    $f = $l->flight;
                                    if (! $f) {
                                        return '<span style="color:#c00">missing flight</span>';
                                    }
                                    return sprintf(
                                        '%s %s %s→%s %s $%s',
                                        e($f->airline->code ?? '?'),
                                        e($f->flight_number),
                                        e($f->fromAirport->code ?? '?'),
                                        e($f->toAirport->code ?? '?'),
                                        $f->departure_date?->format('d.m') ?? '',
                                        number_format((float) $f->price_adult, 0)
                                    );
                                })->implode(' · ');

                                $html .= '<tr style="border-top:1px solid #e5e7eb">';
                                $html .= '<td style="padding:6px 8px;color:#6b7280">#' . $fp->id . '</td>';
                                $html .= '<td style="padding:6px 8px;white-space:nowrap">' . e($fp->departure_date?->format('d.m.Y')) . '</td>';
                                $html .= '<td style="padding:6px 8px">' . $legs . '</td>';
                                $html .= '<td style="padding:6px 8px;text-align:right;font-weight:600">$' . number_format((float) $fp->total_price, 2) . '</td>';
                                $html .= '</tr>';
                            }
                            $html .= '</tbody></table>';

                            return new HtmlString($html);
                        }),
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
                TextColumn::make('generation_status')
                    ->label('Gen status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'queued' => 'gray',
                        'running' => 'warning',
                        'done' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (?string $state, $record) {
                        if (! $state) {
                            return '—';
                        }
                        $sum = $record->generation_summary ?? [];
                        if ($state === 'done') {
                            return sprintf('done (%d new, %d skip)', $sum['created'] ?? 0, $sum['skipped'] ?? 0);
                        }
                        if ($state === 'failed') {
                            return 'failed';
                        }
                        return $state;
                    }),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->recordActions([
                EditAction::make(),
                Action::make('generate_paths')
                    ->label('Generate Paths')
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->requiresConfirmation()
                    ->modalHeading('Queue FlightPath generation')
                    ->modalDescription('Dispatches a background job. The page will auto-refresh; status shows queued → running → done. Idempotent.')
                    ->disabled(fn ($record) => $record->generation_status === 'running' || $record->generation_status === 'queued')
                    ->action(function ($record) {
                        $record->update(['generation_status' => 'queued', 'generation_summary' => null]);
                        GenerateFlightPathsJob::dispatch($record);
                        Notification::make()
                            ->success()
                            ->title('Generation queued')
                            ->body('Background job dispatched. Status column will update as it runs.')
                            ->send();
                    }),
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
