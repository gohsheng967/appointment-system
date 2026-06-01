<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->color('warning')
                ->icon('heroicon-m-pencil-square')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->disabled(fn (): bool => $this->record->status->isTerminal())
                ->extraAttributes(fn (): array => $this->record->status->isTerminal()
                    ? ['class' => 'cursor-not-allowed']
                    : []),
        ];
    }
}
