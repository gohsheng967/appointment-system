<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Services\AppointmentSchedulingResolver;
use App\Domain\Appointments\Services\AppointmentReassignmentAvailabilityService;
use App\Domain\Appointments\Services\StaffAvailabilityLockService;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentAction
{
    public function __construct(
        private readonly AppointmentSchedulingResolver $schedulingResolver,
        private readonly TransitionAppointmentStatusAction $transitionStatusAction,
        private readonly AppointmentReassignmentAvailabilityService $reassignmentAvailabilityService,
        private readonly StaffAvailabilityLockService $staffLockService,
    ) {}

    /**
     * @param  array{
     *   branch_id?: int,
     *   service_id?: int,
     *   customer_id?: int,
     *   staff_id?: int,
     *   start_at?: string,
     *   status?: string,
     *   remark?: string|null,
     *   cancellation_reason?: string|null,
     *   auto_confirm_pending?: bool
     * }  $payload
     */
    public function __invoke(Appointment $appointment, array $payload): Appointment
    {
        return DB::transaction(function () use ($appointment, $payload): Appointment {
            /** @var AppointmentStatus $currentStatus */
            $currentStatus = $appointment->status;
            $targetStatusValue = $payload['status'] ?? null;

            if (
                $currentStatus->isTerminal()
                && collect(['branch_id', 'service_id', 'customer_id', 'staff_id', 'start_at'])
                    ->contains(static fn (string $field): bool => array_key_exists($field, $payload))
            ) {
                throw ValidationException::withMessages([
                    'status' => ['Terminal appointments cannot be rescheduled.'],
                ]);
            }

            if (
                ! isset($targetStatusValue)
                && ($payload['auto_confirm_pending'] ?? false)
                && $currentStatus === AppointmentStatus::PENDING
            ) {
                $targetStatusValue = AppointmentStatus::CONFIRMED->value;
            }

            $effectiveStatus = isset($targetStatusValue)
                ? AppointmentStatus::from($targetStatusValue)
                : $currentStatus;

            if (
                array_key_exists('staff_id', $payload)
                && filled($appointment->staff_id)
                && (int) $payload['staff_id'] !== (int) $appointment->staff_id
            ) {
                $this->reassignmentAvailabilityService->assertCanReassign($appointment);
            }

            $resolved = $this->schedulingResolver->resolveForUpdate(
                $appointment,
                $payload,
                $effectiveStatus->isOngoing(),
            );
            $staff = $resolved['staff'];

            $persistUpdate = function () use (
                $appointment,
                $payload,
                $resolved,
                $staff,
                $targetStatusValue,
            ): Appointment {
                $appointment->fill([
                    'branch_id' => $resolved['branch']->id,
                    'staff_id' => $staff->id,
                    'customer_id' => $resolved['customer']->id,
                    'service_id' => $resolved['service']->id,
                    'start_at' => $resolved['startUtc'],
                    'end_at' => $resolved['endUtc'],
                ]);
                $appointment->save();

                if (isset($targetStatusValue)) {
                    $targetStatus = AppointmentStatus::from($targetStatusValue);

                    $this->transitionStatusAction->__invoke(
                        $appointment,
                        $targetStatus,
                        $payload['remark'] ?? $payload['cancellation_reason'] ?? null,
                    );
                }

                return $appointment->refresh();
            };

            return $this->staffLockService->forStaff($staff->id, $persistUpdate);
        });
    }
}
