<?php

namespace App\Filament\Resources\Resorts;

use App\Filament\Resources\Resorts\Pages\CreateResort;
use App\Filament\Resources\Resorts\Pages\EditResort;
use App\Filament\Resources\Resorts\Pages\ListResorts;
use App\Filament\Resources\Resorts\Schemas\ResortForm;
use App\Filament\Resources\Resorts\Tables\ResortsTable;
use App\Models\Resort;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResortResource extends Resource
{
    protected static ?string $model = Resort::class;

    protected static string|UnitEnum|null $navigationGroup = 'Geography';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ResortForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResortsTable::configure($table);
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
            'index' => ListResorts::route('/'),
            'create' => CreateResort::route('/create'),
            'edit' => EditResort::route('/{record}/edit'),
        ];
    }
}
