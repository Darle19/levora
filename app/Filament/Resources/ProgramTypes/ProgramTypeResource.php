<?php

namespace App\Filament\Resources\ProgramTypes;

use App\Filament\Resources\ProgramTypes\Pages\CreateProgramType;
use App\Filament\Resources\ProgramTypes\Pages\EditProgramType;
use App\Filament\Resources\ProgramTypes\Pages\ListProgramTypes;
use App\Filament\Resources\ProgramTypes\Schemas\ProgramTypeForm;
use App\Filament\Resources\ProgramTypes\Tables\ProgramTypesTable;
use App\Models\ProgramType;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProgramTypeResource extends Resource
{
    protected static ?string $model = ProgramType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tours & Pricing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProgramTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramTypesTable::configure($table);
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
            'index' => ListProgramTypes::route('/'),
            'create' => CreateProgramType::route('/create'),
            'edit' => EditProgramType::route('/{record}/edit'),
        ];
    }
}
