<?php

namespace App\Filament\Resources\SystemMessages;

use App\Filament\Resources\SystemMessages\Pages\CreateSystemMessage;
use App\Filament\Resources\SystemMessages\Pages\EditSystemMessage;
use App\Filament\Resources\SystemMessages\Pages\ListSystemMessages;
use App\Filament\Resources\SystemMessages\Schemas\SystemMessageForm;
use App\Filament\Resources\SystemMessages\Tables\SystemMessagesTable;
use App\Models\SystemMessage;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SystemMessageResource extends Resource
{
    protected static ?string $model = SystemMessage::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SystemMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemMessagesTable::configure($table);
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
            'index' => ListSystemMessages::route('/'),
            'create' => CreateSystemMessage::route('/create'),
            'edit' => EditSystemMessage::route('/{record}/edit'),
        ];
    }
}
