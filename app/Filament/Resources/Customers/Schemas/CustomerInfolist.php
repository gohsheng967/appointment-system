<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone_number'),
                    ]),
                Section::make('Summary')
                    ->columns(5)
                    ->schema([
                        TextEntry::make('total_bookings')
                            ->label('Total Bookings')
                            ->state(static fn (Customer $record): int => $record->appointments()->count())
                            ->badge(),
                        TextEntry::make('completed_bookings')
                            ->label('Completed')
                            ->state(static fn (Customer $record): int => $record->appointments()->where('status', AppointmentStatus::COMPLETED->value)->count())
                            ->badge(),
                        TextEntry::make('cancelled_bookings')
                            ->label('Cancelled')
                            ->state(static fn (Customer $record): int => $record->appointments()->where('status', AppointmentStatus::CANCELLED->value)->count())
                            ->badge(),
                        TextEntry::make('no_show_bookings')
                            ->label('No Show')
                            ->state(static fn (Customer $record): int => $record->appointments()->where('status', AppointmentStatus::NO_SHOW->value)->count())
                            ->badge(),
                        TextEntry::make('last_booking_at')
                            ->label('Last Booking')
                            ->state(static fn (Customer $record): mixed => $record->appointments()->max('start_at'))
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('No bookings'),
                    ]),
            ]);
    }
}

