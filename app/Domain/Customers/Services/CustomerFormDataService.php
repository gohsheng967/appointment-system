<?php

namespace App\Domain\Customers\Services;

use App\Support\PhoneNumberFormDataService;

class CustomerFormDataService
{
    public function __construct(
        private readonly PhoneNumberFormDataService $phoneNumberFormData,
        private readonly CustomerIdentityUniquenessValidator $identityValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForFill(array $data): array
    {
        return $this->phoneNumberFormData->prepareForFill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForSave(array $data, ?int $exceptCustomerId = null): array
    {
        $data = $this->phoneNumberFormData->prepareForSave($data);
        $data['email'] = $this->identityValidator->normalizeEmail($data['email'] ?? null);

        $this->identityValidator->assertAtLeastOneContact(
            $data['email'],
            $data['phone_number'],
        );

        $this->identityValidator->assertUniqueIdentity(
            $data['email'],
            $data['phone_number'],
            $exceptCustomerId,
        );

        return $data;
    }
}
