<?php

namespace App\Support;

class PhoneNumberSubmissionNormalizer
{
    public function normalize(?string $countryCode, mixed $submittedPhoneNumber): ?string
    {
        $trimmedPhoneNumber = trim((string) $submittedPhoneNumber);

        if ($trimmedPhoneNumber === '') {
            return null;
        }

        if (str_starts_with($trimmedPhoneNumber, '+')) {
            return normalize_phone_number($trimmedPhoneNumber);
        }

        return CustomerPhoneNumberFormState::composeForStorage($countryCode, $trimmedPhoneNumber);
    }

    public function isValid(?string $phoneNumber): bool
    {
        return PhoneNumberService::isInternational($phoneNumber);
    }

    public function validationMessage(): string
    {
        return 'Phone number must be in international format (E.164), for example +60123456789.';
    }
}
