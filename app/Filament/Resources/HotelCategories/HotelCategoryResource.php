<?php

namespace App\Filament\Resources\HotelCategories;

use App\Filament\Resources\HotelCategories\Pages\CreateHotelCategory;
use App\Filament\Resources\HotelCategories\Pages\EditHotelCategory;
use App\Filament\Resources\HotelCategories\Pages\ListHotelCategories;
use App\Filament\Resources\HotelCategories\Schemas\HotelCategoryForm;
use App\Filament\Resources\HotelCategories\Tables\HotelCategoriesTable;
use App\Models\HotelCategory;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HotelCategoryResource extends Resource
{
    protected static ?string $model = HotelCategory::class;

    protected static string|UnitEnum|null $navigationGroup = 'Hotels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return HotelCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotelCategoriesTable::configure($table);
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
            'index' => ListHotelCategories::route('/'),
            'create' => CreateHotelCategory::route('/create'),
            'edit' => EditHotelCategory::route('/{record}/edit'),
        ];
    }
}
