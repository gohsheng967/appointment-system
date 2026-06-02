<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Service updated successfully.';
    }

    protected function getHeaderActions(): array
    {
        $hasActiveAppointments = $this->record->hasActiveAppointments();

        return [
            ViewAction::make()->color('success'),
            DeleteAction::make()
                ->disabled($hasActiveAppointments)
                ->successNotificationTitle('Service deleted successfully.')
                ->tooltip($hasActiveAppointments
                    ? 'Cannot delete service with active appointments (Confirmed or In Progress).'
                    : null),
        ];
    }
}
