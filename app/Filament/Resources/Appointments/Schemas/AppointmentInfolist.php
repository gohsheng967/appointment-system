<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Appointment Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('booking_reference')
                            ->label('Reference')
                            ->icon('heroicon-m-hashtag'),
                        TextEntry::make('branch.name')
                            ->label('Branch')
                            ->icon('heroicon-m-building-storefront'),
                        TextEntry::make('service.name')
                            ->label('Service')
                            ->icon('heroicon-m-briefcase'),
                        TextEntry::make('staff.name')
                            ->label('Staff')
                            ->icon('heroicon-m-user'),
                        TextEntry::make('customer.name')
                            ->label('Customer')
                            ->icon('heroicon-m-user-circle'),
                    ]),
                Section::make('Schedule & Status')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('start_at')
                            ->label('Start At')
                            ->dateTime('D, d M Y H:i')
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('end_at')
                            ->label('End At')
                            ->dateTime('D, d M Y H:i')
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('duration')
                            ->label('Duration')
                            ->state(static function (Appointment $record): string {
                                return (string) $record->start_at->diffInMinutes($record->end_at).' mins';
                            })
                            ->icon('heroicon-m-clock'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(static fn (AppointmentStatus|string|null $state): string => AppointmentStatus::labelFor($state))
                            ->color(static fn (AppointmentStatus|string|null $state): string => AppointmentStatus::colorFor($state)),
                        TextEntry::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
