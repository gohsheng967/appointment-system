<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                Select::make('role')
                    ->options(UserRole::options())
                    ->default(UserRole::STAFF->value)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === UserRole::ADMIN->value) {
                            $set('branch_id', null);
                        }
                    }),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(function (Get $get, ?User $record): bool {
                        if ($get('role') === UserRole::ADMIN->value) {
                            return true;
                        }

                        if (! $record || $record->role !== UserRole::STAFF) {
                            return false;
                        }

                        return $record->hasActiveAppointments();
                    })
                    ->helperText(function (?User $record): ?string {
                        if (! $record || $record->role !== UserRole::STAFF) {
                            return null;
                        }

                        return $record->hasActiveAppointments()
                            ? 'Branch cannot be changed while this staff has active appointments (Confirmed or In Progress).'
                            : null;
                    })
                    ->required(fn (Get $get): bool => $get('role') === UserRole::STAFF->value),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8),
            ]);
    }
}
