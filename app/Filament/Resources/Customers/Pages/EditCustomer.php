<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Support\CustomerPhoneNumberFormState;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

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
        ];
    }
}
