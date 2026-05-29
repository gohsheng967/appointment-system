<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (): bool => auth()->user()?->isStaff() ?? false),
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (): bool => auth()->user()?->isStaff() ?? false),
                Select::make('staff_id')
                    ->relationship(
                        name: 'staff',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('role', UserRole::STAFF->value),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (): bool => auth()->user()?->isStaff() ?? false),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (): bool => auth()->user()?->isStaff() ?? false),
                TextInput::make('start_at_local')
                    ->label('Start Datetime (Branch Local)')
                    ->type('datetime-local')
                    ->required()
                    ->helperText('Enter the datetime in the selected branch timezone.')
                    ->disabled(fn (): bool => auth()->user()?->isStaff() ?? false),
                Select::make('status')
                    ->options(AppointmentStatus::options())
                    ->default(AppointmentStatus::PENDING->value)
                    ->required()
                    ->hiddenOn('create'),
                Textarea::make('cancellation_reason')
                    ->nullable()
                    ->rows(3)
                    ->helperText('Required when status is cancelled.')
                    ->hiddenOn('create'),
            ]);
    }
}
