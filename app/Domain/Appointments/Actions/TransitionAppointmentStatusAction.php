<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Services\AppointmentOverlapService;
use App\Domain\Appointments\Services\StaffAvailabilityLockService;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

class TransitionAppointmentStatusAction
{
    public function __construct(
        private readonly AppointmentOverlapService $overlapService,
        private readonly StaffAvailabilityLockService $staffLockService,
    ) {}

    public function __invoke(
        Appointment $appointment,
        AppointmentStatus $targetStatus,
        ?string $cancellationReason = null,
    ): Appointment {
        /** @var AppointmentStatus $currentStatus */
        $currentStatus = $appointment->status;

        if (! $currentStatus->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition from {$currentStatus->value} to {$targetStatus->value}."],
            ]);
        }

        if ($targetStatus === AppointmentStatus::CANCELLED && blank($cancellationReason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => ['A cancellation reason is required when cancelling an appointment.'],
            ]);
        }

        $transition = function () use ($appointment, $targetStatus, $cancellationReason): Appointment {
            if ($targetStatus === AppointmentStatus::CONFIRMED) {
                $this->overlapService->assertNoOverlap(
                    (int) $appointment->staff_id,
                    $appointment->start_at->toImmutable(),
                    $appointment->end_at->toImmutable(),
                    $appointment->id,
                );
            }

            $appointment->status = $targetStatus;
            $appointment->cancellation_reason = $targetStatus === AppointmentStatus::CANCELLED
                ? trim((string) $cancellationReason)
                : null;
            $appointment->save();

            return $appointment;
        };

        if ($targetStatus === AppointmentStatus::CONFIRMED) {
            if (! $appointment->staff_id) {
                throw ValidationException::withMessages([
                    'staff_id' => ['Please assign staff before confirming this booking.'],
                ]);
            }

            return $this->staffLockService->forStaff((int) $appointment->staff_id, $transition);
        }

        return $transition();
    }
}
