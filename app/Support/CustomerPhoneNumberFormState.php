<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CustomerPhoneNumberFormState
{
    public const DEFAULT_COUNTRY_CODE = '+60';

    /**
     * @return array{phone_country_code: string, phone_number: string}
     */
    public static function splitForForm(?string $storedPhoneNumber): array
    {
        if (blank($storedPhoneNumber)) {
            return [
                'phone_country_code' => self::DEFAULT_COUNTRY_CODE,
                'phone_number' => '',
            ];
        }

        $normalized = normalize_phone_number($storedPhoneNumber);

        if (! is_string($normalized) || ! str_starts_with($normalized, '+')) {
            return [
                'phone_country_code' => self::DEFAULT_COUNTRY_CODE,
                'phone_number' => (string) $storedPhoneNumber,
            ];
        }

        if (str_starts_with($normalized, self::DEFAULT_COUNTRY_CODE)) {
            return [
                'phone_country_code' => self::DEFAULT_COUNTRY_CODE,
                'phone_number' => substr($normalized, strlen(self::DEFAULT_COUNTRY_CODE)),
            ];
        }

        return [
            'phone_country_code' => self::DEFAULT_COUNTRY_CODE,
            'phone_number' => ltrim($normalized, '+'),
        ];
    }

    public static function composeForStorage(?string $countryCode, ?string $localNumber): ?string
    {
        $trimmed = trim((string) $localNumber);

        if ($trimmed === '') {
            return null;
        }

        $countryCode = filled($countryCode) ? (string) $countryCode : self::DEFAULT_COUNTRY_CODE;
        $countryDigits = ltrim($countryCode, '+');

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($countryDigits !== '' && str_starts_with($digits, $countryDigits)) {
            $digits = substr($digits, strlen($countryDigits));
        }

        $digits = ltrim($digits, '0');

        if ($digits === '') {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number is invalid.'],
            ]);
        }

        $normalized = normalize_phone_number($countryCode.$digits);

        if (! PhoneNumberService::isInternational($normalized)) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number must be in international format (E.164), for example +60123456789.'],
            ]);
        }

        return $normalized;
    }
}
