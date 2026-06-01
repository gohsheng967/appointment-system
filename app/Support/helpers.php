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

if (! function_exists('timezone_utc_offset_label')) {
    function timezone_utc_offset_label(string $timezone, ?CarbonInterface $at = null): string
    {
        ensure_valid_timezone($timezone);

        $local = $at instanceof CarbonInterface
            ? CarbonImmutable::instance($at)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);

        $offsetSeconds = $local->getOffset();
        $sign = $offsetSeconds >= 0 ? '+' : '-';
        $absolute = abs($offsetSeconds);
        $hours = intdiv($absolute, 3600);
        $minutes = intdiv($absolute % 3600, 60);

        if ($minutes === 0) {
            return sprintf('UTC%s%d', $sign, $hours);
        }

        return sprintf('UTC%s%d:%02d', $sign, $hours, $minutes);
    }
}

if (! function_exists('normalize_phone_number')) {
    function normalize_phone_number(?string $value): ?string
    {
        return PhoneNumberService::normalize($value);
    }
}
