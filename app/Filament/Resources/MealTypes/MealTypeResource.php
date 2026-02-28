<?php

namespace App\Filament\Resources\MealTypes;

use App\Filament\Resources\MealTypes\Pages\CreateMealType;
use App\Filament\Resources\MealTypes\Pages\EditMealType;
use App\Filament\Resources\MealTypes\Pages\ListMealTypes;
use App\Filament\Resources\MealTypes\Schemas\MealTypeForm;
use App\Filament\Resources\MealTypes\Tables\MealTypesTable;
use App\Models\MealType;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MealTypeResource extends Resource
{
    protected static ?string $model = MealType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Hotels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return MealTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MealTypesTable::configure($table);
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
            'index' => ListMealTypes::route('/'),
            'create' => CreateMealType::route('/create'),
            'edit' => EditMealType::route('/{record}/edit'),
        ];
    }
}
