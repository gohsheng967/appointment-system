<?php

namespace App\Domain\Appointments\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class AppointmentOverlapService
{
    public function hasOverlap(
        int $staffId,
        CarbonInterface $startUtc,
        CarbonInterface $endUtc,
        ?int $exceptAppointmentId = null,
    ): bool {
        $query = Appointment::query()
            ->where('staff_id', $staffId)
            ->whereIn(
                'status',
                array_map(
                    static fn (AppointmentStatus $status) => $status->value,
                    AppointmentStatus::blockingStatuses(),
                ),
            )
            ->where('start_at', '<', $endUtc->toDateTimeString())
            ->where('end_at', '>', $startUtc->toDateTimeString());

        if ($exceptAppointmentId !== null) {
            $query->whereKeyNot($exceptAppointmentId);
        }

        return $query->exists();
    }

    public function assertNoOverlap(
        int $staffId,
        CarbonInterface $startUtc,
        CarbonInterface $endUtc,
        ?int $exceptAppointmentId = null,
    ): void {
        if ($this->hasOverlap($staffId, $startUtc, $endUtc, $exceptAppointmentId)) {
            throw ValidationException::withMessages([
                'staff_id' => ['The selected staff member is not available for this time range.'],
            ]);
        }
    }
}
