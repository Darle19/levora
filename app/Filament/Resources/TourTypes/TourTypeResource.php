<?php

namespace App\Filament\Resources\TourTypes;

use App\Filament\Resources\TourTypes\Pages\CreateTourType;
use App\Filament\Resources\TourTypes\Pages\EditTourType;
use App\Filament\Resources\TourTypes\Pages\ListTourTypes;
use App\Filament\Resources\TourTypes\Schemas\TourTypeForm;
use App\Filament\Resources\TourTypes\Tables\TourTypesTable;
use App\Models\TourType;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TourTypeResource extends Resource
{
    protected static ?string $model = TourType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TourTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TourTypesTable::configure($table);
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
            'index' => ListTourTypes::route('/'),
            'create' => CreateTourType::route('/create'),
            'edit' => EditTourType::route('/{record}/edit'),
        ];
    }
}
