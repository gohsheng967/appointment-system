<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('branch.name')->label('Branch'),
                TextEntry::make('service.name')->label('Service'),
                TextEntry::make('staff.name')->label('Staff'),
                TextEntry::make('customer.name')->label('Customer'),
                TextEntry::make('start_at')->dateTime('Y-m-d H:i'),
                TextEntry::make('end_at')->dateTime('Y-m-d H:i'),
                TextEntry::make('status'),
                TextEntry::make('cancellation_reason')->placeholder('-'),
            ]);
    }
}
