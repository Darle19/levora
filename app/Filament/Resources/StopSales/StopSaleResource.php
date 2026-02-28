<?php

namespace App\Filament\Resources\StopSales;

use App\Filament\Resources\StopSales\Pages\CreateStopSale;
use App\Filament\Resources\StopSales\Pages\EditStopSale;
use App\Filament\Resources\StopSales\Pages\ListStopSales;
use App\Filament\Resources\StopSales\Schemas\StopSaleForm;
use App\Filament\Resources\StopSales\Tables\StopSalesTable;
use App\Models\StopSale;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StopSaleResource extends Resource
{
    protected static ?string $model = StopSale::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StopSaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StopSalesTable::configure($table);
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
            'index' => ListStopSales::route('/'),
            'create' => CreateStopSale::route('/create'),
            'edit' => EditStopSale::route('/{record}/edit'),
        ];
    }
}
