<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('timezone')
                    ->label('Timezone (IANA)')
                    ->required()
                    ->rule('timezone')
                    ->maxLength(100),
                TimePicker::make('opening_time')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('closing_time')
                    ->seconds(false)
                    ->required(),
                TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->required()
                    ->maxLength(20)
                    ->regex('/^\+[1-9]\d{7,14}$/')
                    ->helperText('Use international format, e.g. +60123456789'),
            ]);
    }
}
