<?php

namespace App\Filament\Resources\HotelAmenityTypes;

use App\Filament\Resources\HotelAmenityTypes\Pages\CreateHotelAmenityType;
use App\Filament\Resources\HotelAmenityTypes\Pages\EditHotelAmenityType;
use App\Filament\Resources\HotelAmenityTypes\Pages\ListHotelAmenityTypes;
use App\Filament\Resources\HotelAmenityTypes\Schemas\HotelAmenityTypeForm;
use App\Filament\Resources\HotelAmenityTypes\Tables\HotelAmenityTypesTable;
use App\Models\HotelAmenityType;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HotelAmenityTypeResource extends Resource
{
    protected static ?string $model = HotelAmenityType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Hotels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return HotelAmenityTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotelAmenityTypesTable::configure($table);
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
            'index' => ListHotelAmenityTypes::route('/'),
            'create' => CreateHotelAmenityType::route('/create'),
            'edit' => EditHotelAmenityType::route('/{record}/edit'),
        ];
    }
}
