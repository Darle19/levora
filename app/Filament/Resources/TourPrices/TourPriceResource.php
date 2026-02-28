<?php

namespace App\Filament\Resources\TourPrices;

use App\Filament\Resources\TourPrices\Pages\CreateTourPrice;
use App\Filament\Resources\TourPrices\Pages\EditTourPrice;
use App\Filament\Resources\TourPrices\Pages\ListTourPrices;
use App\Filament\Resources\TourPrices\Schemas\TourPriceForm;
use App\Filament\Resources\TourPrices\Tables\TourPricesTable;
use App\Models\TourPrice;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TourPriceResource extends Resource
{
    protected static ?string $model = TourPrice::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TourPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TourPricesTable::configure($table);
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
            'index' => ListTourPrices::route('/'),
            'create' => CreateTourPrice::route('/create'),
            'edit' => EditTourPrice::route('/{record}/edit'),
        ];
    }
}
