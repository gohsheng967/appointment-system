<?php

namespace App\Support;

class PhoneNumberService
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9+]/', '', $value) ?? '';

        if (str_starts_with($normalized, '00')) {
            $normalized = '+'.substr($normalized, 2);
        }

        if (! str_starts_with($normalized, '+')) {
            $normalized = '+'.ltrim($normalized, '+');
        }

        return $normalized;
    }

    public static function isInternational(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $value);
    }
}
