<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->nullable()
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->nullable()
                    ->maxLength(20)
                    ->regex('/^\+[1-9]\d{7,14}$/')
                    ->helperText('Use international format, e.g. +60123456789'),
            ]);
    }
}
