<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Profile')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->icon('heroicon-m-user')
                            ->weight('semibold'),
                        TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->copyMessage('Email copied'),
                        TextEntry::make('role')
                            ->badge()
                            ->formatStateUsing(static fn (UserRole|string|null $state): string => UserRole::labelFor($state))
                            ->color(static function (UserRole|string|null $state): string {
                                $role = $state instanceof UserRole
                                    ? $state
                                    : (is_string($state) ? UserRole::tryFrom($state) : null);

                                return $role === UserRole::ADMIN ? 'info' : 'success';
                            }),
                        TextEntry::make('branch.name')
                            ->label('Branch')
                            ->icon('heroicon-m-building-storefront')
                            ->placeholder('No branch assigned'),
                    ]),
                Section::make('Appointment Snapshot')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('appointments_total')
                            ->label('Total')
                            ->state(static fn (User $record): int => $record->appointments()->count())
                            ->badge(),
                        TextEntry::make('appointments_confirmed')
                            ->label('Confirmed')
                            ->state(static fn (User $record): int => $record->appointments()->where('status', AppointmentStatus::CONFIRMED->value)->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('appointments_in_progress')
                            ->label('In Progress')
                            ->state(static fn (User $record): int => $record->appointments()->where('status', AppointmentStatus::IN_PROGRESS->value)->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('appointments_completed')
                            ->label('Completed')
                            ->state(static fn (User $record): int => $record->appointments()->where('status', AppointmentStatus::COMPLETED->value)->count())
                            ->badge()
                            ->color('success'),
                    ]),
                Section::make('Audit')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),
                        TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('Active'),
                    ]),
            ]);
    }
}
