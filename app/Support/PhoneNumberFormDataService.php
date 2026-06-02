<?php

namespace App\Support;

class PhoneNumberFormDataService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForFill(array $data): array
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
    public function prepareForSave(array $data): array
    {
        $data['phone_number'] = CustomerPhoneNumberFormState::composeForStorage(
            $data['phone_country_code'] ?? CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE,
            $data['phone_number'] ?? null,
        );

        unset($data['phone_country_code']);

        return $data;
    }
}
