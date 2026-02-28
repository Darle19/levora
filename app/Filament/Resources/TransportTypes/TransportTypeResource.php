<?php

namespace App\Filament\Resources\TransportTypes;

use App\Filament\Resources\TransportTypes\Pages\CreateTransportType;
use App\Filament\Resources\TransportTypes\Pages\EditTransportType;
use App\Filament\Resources\TransportTypes\Pages\ListTransportTypes;
use App\Filament\Resources\TransportTypes\Schemas\TransportTypeForm;
use App\Filament\Resources\TransportTypes\Tables\TransportTypesTable;
use App\Models\TransportType;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransportTypeResource extends Resource
{
    protected static ?string $model = TransportType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Flights';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TransportTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransportTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransportTypes::route('/'),
            'create' => CreateTransportType::route('/create'),
            'edit' => EditTransportType::route('/{record}/edit'),
        ];
    }
}
