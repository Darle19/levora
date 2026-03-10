<?php

namespace App\Filament\Resources\Hotels\RelationManagers;

use App\Models\Currency;
use App\Models\MealType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupOffersRelationManager extends RelationManager
{
    protected static string $relationship = 'groupOffers';

    protected static ?string $title = 'Group Offers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->placeholder('Series Group Offer - April-June 2026'),
                TagsInput::make('check_in_dates')
                    ->label('Check-in Dates')
                    ->placeholder('13.04.2026')
                    ->required(),
                TextInput::make('nights')
                    ->label('Nights')
                    ->numeric()
                    ->default(6)
                    ->required(),
                TextInput::make('pax_count')
                    ->label('Pax Count')
                    ->numeric()
                    ->required(),
                TextInput::make('rooms_count')
                    ->label('Rooms Count')
                    ->numeric()
                    ->required(),
                TextInput::make('room_configuration')
                    ->label('Room Configuration')
                    ->placeholder('15 double rooms - French bed')
                    ->required(),
                TextInput::make('nationality')
                    ->label('Nationality')
                    ->placeholder('Uzbek'),
                TextInput::make('rate_per_night')
                    ->label('Rate Per Night')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Select::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::where('is_active', true)->pluck('code', 'id'))
                    ->required()
                    ->default(fn () => Currency::where('code', 'USD')->value('id')),
                Select::make('meal_type_id')
                    ->label('Meal Type')
                    ->options(MealType::where('is_active', true)->pluck('name_en', 'id'))
                    ->searchable(),
                Textarea::make('cancellation_policy')
                    ->label('Cancellation Policy')
                    ->placeholder('Non-refundable. Prepayment required. No cancellation, refund, or modification allowed.'),
                Textarea::make('notes')
                    ->label('Notes'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),
                TextColumn::make('pax_count')
                    ->label('Pax'),
                TextColumn::make('rooms_count')
                    ->label('Rooms'),
                TextColumn::make('nights')
                    ->label('Nights'),
                TextColumn::make('rate_per_night')
                    ->money()
                    ->label('Rate/Night'),
                TextColumn::make('currency.code')
                    ->label('Currency'),
                TextColumn::make('room_configuration')
                    ->label('Config')
                    ->limit(30),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
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
