<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->color('success'),
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->hasActiveAppointments())
                ->tooltip(fn (): ?string => $this->record->hasActiveAppointments()
                    ? 'Cannot delete service with active appointments (Confirmed or In Progress).'
                    : null),
        ];
    }
}
