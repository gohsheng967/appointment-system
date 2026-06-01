<?php

namespace App\Filament\Resources\Branches\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Support\CustomerPhoneNumberFormState;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['phone_number'] = CustomerPhoneNumberFormState::composeForStorage(
            $data['phone_country_code'] ?? CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE,
            $data['phone_number'] ?? null,
        );

        unset($data['phone_country_code']);

        return $data;
    }
}
