<?php

namespace App\Domain\Appointments\Data;

readonly class AppointmentReassignmentAvailability
{
    /**
     * @param  array<int, string>  $staffOptions
     */
    public function __construct(
        public bool $blockedByStatus,
        public array $staffOptions,
    ) {}

    public function isAvailable(): bool
    {
        return (! $this->blockedByStatus) && $this->hasAvailableStaffOptions();
    }

    public function hasAvailableStaffOptions(): bool
    {
        return $this->staffOptions !== [];
    }
}
