<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use App\Models\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'transfers';

    protected static ?string $title = 'Transfer Pricing';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('route_description')
                    ->label('Route')
                    ->placeholder('Airport - Hotel - Airport')
                    ->required(),
                TextInput::make('vehicle_name')
                    ->label('Vehicle')
                    ->placeholder('Mercedes Vito')
                    ->required(),
                TextInput::make('vehicle_year')
                    ->label('Year')
                    ->placeholder('2016'),
                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Select::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::where('is_active', true)->pluck('code', 'id'))
                    ->required()
                    ->default(fn () => Currency::where('code', 'USD')->value('id')),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('route_description')
                    ->label('Route')
                    ->searchable(),
                TextColumn::make('vehicle_name')
                    ->label('Vehicle')
                    ->searchable(),
                TextColumn::make('vehicle_year')
                    ->label('Year'),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency.code')
                    ->label('Currency'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('price')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
