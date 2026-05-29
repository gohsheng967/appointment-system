<?php

namespace App\Domain\Appointments\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

class TransitionAppointmentStatusAction
{
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

        $appointment->status = $targetStatus;
        $appointment->cancellation_reason = $targetStatus === AppointmentStatus::CANCELLED
            ? trim((string) $cancellationReason)
            : null;
        $appointment->save();

        return $appointment;
    }
}
