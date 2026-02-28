<?php

namespace App\Filament\Resources\TransportTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TransportTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('name_ru')
                    ->required(),
                TextInput::make('name_uz')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
