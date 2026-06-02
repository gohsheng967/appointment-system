<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Appointments\Data\AppointmentReassignmentAvailability;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AppointmentReassignmentAvailabilityService
{
    public function evaluate(Appointment $appointment): AppointmentReassignmentAvailability
    {
        $blockedByStatus = ! $this->hasEligibleStatus($appointment);

        return new AppointmentReassignmentAvailability(
            blockedByStatus: $blockedByStatus,
            staffOptions: $blockedByStatus ? [] : $this->getAvailableStaffOptions($appointment),
        );
    }

    public function assertCanReassign(Appointment $appointment): void
    {
        if ($this->hasEligibleStatus($appointment)) {
            return;
        }

        throw ValidationException::withMessages([
            'staff_id' => ['Staff reassignment is only allowed for confirmed appointments.'],
        ]);
    }

    private function hasEligibleStatus(Appointment $appointment): bool
    {
        return $appointment->status === AppointmentStatus::CONFIRMED;
    }

    /**
     * @return array<int, string>
     */
    private function getAvailableStaffOptions(Appointment $appointment): array
    {
        return User::query()
            ->where('role', UserRole::STAFF->value)
            ->where('branch_id', $appointment->branch_id)
            ->when(
                filled($appointment->staff_id),
                fn (Builder $query): Builder => $query->whereKeyNot($appointment->staff_id),
            )
            ->whereDoesntHave('appointments', function (Builder $query) use ($appointment): void {
                $query
                    ->whereIn(
                        'status',
                        array_map(
                            static fn (AppointmentStatus $status): string => $status->value,
                            AppointmentStatus::blockingStatuses(),
                        ),
                    )
                    ->where('start_at', '<', $appointment->end_at->toDateTimeString())
                    ->where('end_at', '>', $appointment->start_at->toDateTimeString())
                    ->whereKeyNot($appointment->getKey());
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
