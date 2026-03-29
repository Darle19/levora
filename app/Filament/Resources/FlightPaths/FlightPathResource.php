<?php

namespace App\Filament\Resources\FlightPaths;

use App\Filament\Resources\FlightPaths\Pages\ListFlightPaths;
use App\Filament\Resources\FlightPaths\Pages\EditFlightPath;
use App\Filament\Resources\FlightPaths\Tables\FlightPathsTable;
use App\Models\FlightPath;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FlightPathResource extends Resource
{
    protected static ?string $model = FlightPath::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Flight Paths';

    protected static ?int $navigationSort = 0;

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
