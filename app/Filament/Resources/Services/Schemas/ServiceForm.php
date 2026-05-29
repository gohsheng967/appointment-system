<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('duration_minutes')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(600),
                FileUpload::make('image')
                    ->image()
                    ->directory('services')
                    ->nullable(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->nullable(),
                Textarea::make('description')
                    ->rows(4)
                    ->nullable(),
            ]);
    }
}
