<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Services\AppointmentOverlapService;
use App\Domain\Appointments\Services\BranchOperatingHoursService;
use App\Domain\Appointments\Services\StaffAvailabilityLockService;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentAction
{
    public function __construct(
        private readonly BranchOperatingHoursService $operatingHoursService,
        private readonly AppointmentOverlapService $overlapService,
        private readonly TransitionAppointmentStatusAction $transitionStatusAction,
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
     *   cancellation_reason?: string|null,
     *   auto_confirm_pending?: bool
     * }  $payload
     */
    public function __invoke(Appointment $appointment, array $payload): Appointment
    {
        return DB::transaction(function () use ($appointment, $payload): Appointment {
            /** @var AppointmentStatus $currentStatus */
            $currentStatus = $appointment->status;

            if (
                $currentStatus->isTerminal()
                && collect(['branch_id', 'service_id', 'customer_id', 'staff_id', 'start_at'])
                    ->contains(static fn (string $field): bool => array_key_exists($field, $payload))
            ) {
                throw ValidationException::withMessages([
                    'status' => ['Terminal appointments cannot be rescheduled.'],
                ]);
            }

            $branch = Branch::query()->findOrFail($payload['branch_id'] ?? $appointment->branch_id);
            $service = Service::query()->findOrFail($payload['service_id'] ?? $appointment->service_id);
            $customer = Customer::query()->findOrFail($payload['customer_id'] ?? $appointment->customer_id);
            $staff = User::query()->findOrFail($payload['staff_id'] ?? $appointment->staff_id);

            $startUtc = isset($payload['start_at'])
                ? branch_local_to_utc($payload['start_at'], $branch->timezone)
                : $appointment->start_at->toImmutable();
            $endUtc = $startUtc->addMinutes($service->duration_minutes);

            $persistUpdate = function () use ($appointment, $payload, $branch, $service, $customer, $staff, $startUtc, $endUtc): Appointment {
                $this->assertStaffIsAssignableToBranch($staff, $branch);
                $this->operatingHoursService->assertWithinHours($branch, $startUtc, $endUtc);
                $this->overlapService->assertNoOverlap($staff->id, $startUtc, $endUtc, $appointment->id);

                $appointment->fill([
                    'branch_id' => $branch->id,
                    'staff_id' => $staff->id,
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'start_at' => $startUtc,
                    'end_at' => $endUtc,
                ]);
                $appointment->save();

                $targetStatusValue = $payload['status'] ?? null;
                if (
                    ! isset($targetStatusValue)
                    && ($payload['auto_confirm_pending'] ?? false)
                    && $currentStatus === AppointmentStatus::PENDING
                ) {
                    $targetStatusValue = AppointmentStatus::CONFIRMED->value;
                }

                if (isset($targetStatusValue)) {
                    $targetStatus = AppointmentStatus::from($targetStatusValue);

                    $this->transitionStatusAction->__invoke(
                        $appointment,
                        $targetStatus,
                        $payload['cancellation_reason'] ?? null,
                    );
                }

                return $appointment->refresh();
            };

            return $this->staffLockService->forStaff($staff->id, $persistUpdate);
        });
    }

    private function assertStaffIsAssignableToBranch(User $staff, Branch $branch): void
    {
        if ($staff->role !== UserRole::STAFF) {
            throw ValidationException::withMessages([
                'staff_id' => ['Only staff users can be assigned to appointments.'],
            ]);
        }

        if ((int) $staff->branch_id !== (int) $branch->id) {
            throw ValidationException::withMessages([
                'staff_id' => ['Selected staff does not belong to the selected branch.'],
            ]);
        }
    }
}
