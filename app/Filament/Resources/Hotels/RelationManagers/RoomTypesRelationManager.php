<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'roomTypes';

    protected static ?string $title = 'Room Types & Pricing';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('price_per_night')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->label('Price Per Night'),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('Room Type')
                    ->searchable(),
                TextColumn::make('price_per_night')
                    ->money()
                    ->sortable()
                    ->label('Price/Night'),
                TextColumn::make('max_adults')
                    ->label('Max Adults'),
                TextColumn::make('max_children')
                    ->label('Max Children'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('price_per_night')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->label('Price Per Night'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
