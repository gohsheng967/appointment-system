<?php

use App\Support\PhoneNumberService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

if (! function_exists('branch_local_to_utc')) {
    function branch_local_to_utc(string|CarbonInterface $value, string $timezone): CarbonImmutable
    {
        $local = $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)->setTimezone($timezone)
            : CarbonImmutable::parse($value, $timezone);

        return $local->utc();
    }
}

if (! function_exists('utc_to_branch_local')) {
    function utc_to_branch_local(CarbonInterface $value, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::instance($value)->setTimezone($timezone);
    }
}

if (! function_exists('ensure_valid_timezone')) {
    function ensure_valid_timezone(string $timezone): void
    {
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException("Invalid timezone [{$timezone}].");
        }
    }
}

if (! function_exists('normalize_phone_number')) {
    function normalize_phone_number(?string $value): ?string
    {
        return PhoneNumberService::normalize($value);
    }
}
