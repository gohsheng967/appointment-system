<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Support\CustomerPhoneNumberFormState;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $phoneParts = CustomerPhoneNumberFormState::splitForForm($data['phone_number'] ?? null);

        $data['phone_country_code'] = $phoneParts['phone_country_code'];
        $data['phone_number'] = $phoneParts['phone_number'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['phone_number'] = CustomerPhoneNumberFormState::composeForStorage(
            $data['phone_country_code'] ?? CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE,
            $data['phone_number'] ?? null,
        );

        unset($data['phone_country_code']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->color('success'),
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->hasActiveAppointments())
                ->tooltip(fn (): ?string => $this->record->hasActiveAppointments()
                    ? 'Cannot delete branch with active appointments (Confirmed or In Progress).'
                    : null),
        ];
    }
}
