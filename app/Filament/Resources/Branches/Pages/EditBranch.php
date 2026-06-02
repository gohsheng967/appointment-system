<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Support\PhoneNumberFormDataService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Branch updated successfully.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return app(PhoneNumberFormDataService::class)->prepareForFill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(PhoneNumberFormDataService::class)->prepareForSave($data);
    }

    protected function getHeaderActions(): array
    {
        $hasActiveAppointments = $this->record->hasActiveAppointments();

        return [
            ViewAction::make()->color('success'),
            DeleteAction::make()
                ->disabled($hasActiveAppointments)
                ->successNotificationTitle('Branch deleted successfully.')
                ->tooltip($hasActiveAppointments
                    ? 'Cannot delete branch with active appointments (Confirmed or In Progress).'
                    : null),
        ];
    }
}
