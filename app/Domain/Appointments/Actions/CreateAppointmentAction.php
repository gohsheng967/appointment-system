<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Services\AppointmentSchedulingResolver;
use App\Domain\Appointments\Services\StaffAvailabilityLockService;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class CreateAppointmentAction
{
    public function __construct(
        private readonly AppointmentSchedulingResolver $schedulingResolver,
        private readonly StaffAvailabilityLockService $staffLockService,
    ) {}

    /**
     * @param  array{
     *   branch_id: int,
     *   service_id: int,
     *   customer_id: int,
     *   start_at: string,
     *   staff_id?: int|null
     * }  $payload
     */
    public function __invoke(array $payload): Appointment
    {
        $resolved = $this->schedulingResolver->resolveForCreate($payload);
        $staff = $resolved['staff'];

        $createAppointment = fn (): Appointment => DB::transaction(
            fn (): Appointment => Appointment::query()->create([
                'branch_id' => $resolved['branch']->id,
                'staff_id' => $staff?->id,
                'customer_id' => $resolved['customer']->id,
                'service_id' => $resolved['service']->id,
                'start_at' => $resolved['startUtc'],
                'end_at' => $resolved['endUtc'],
                'status' => AppointmentStatus::PENDING,
                'cancellation_reason' => null,
            ]),
        );

        if (! $staff) {
            return $createAppointment();
        }

        return $this->staffLockService->forStaff($staff->id, $createAppointment);
    }
}
