<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Services\AppointmentOverlapService;
use App\Domain\Appointments\Services\BranchOperatingHoursService;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAppointmentAction
{
    public function __construct(
        private readonly BranchOperatingHoursService $operatingHoursService,
        private readonly AppointmentOverlapService $overlapService,
        private readonly AssignAvailableStaffAction $assignAvailableStaffAction,
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
        $branch = Branch::query()->findOrFail($payload['branch_id']);
        $service = Service::query()->findOrFail($payload['service_id']);
        $customer = Customer::query()->findOrFail($payload['customer_id']);

        $startUtc = branch_local_to_utc($payload['start_at'], $branch->timezone);
        $endUtc = $startUtc->addMinutes($service->duration_minutes);

        $this->operatingHoursService->assertWithinHours($branch, $startUtc, $endUtc);

        $staff = isset($payload['staff_id']) && $payload['staff_id']
            ? User::query()->findOrFail($payload['staff_id'])
            : ($this->assignAvailableStaffAction)($branch, $startUtc, $endUtc);

        $this->assertStaffIsAssignableToBranch($staff, $branch);
        $this->overlapService->assertNoOverlap($staff->id, $startUtc, $endUtc);

        return DB::transaction(function () use ($branch, $service, $customer, $staff, $startUtc, $endUtc): Appointment {
            return Appointment::query()->create([
                'branch_id' => $branch->id,
                'staff_id' => $staff->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'start_at' => $startUtc,
                'end_at' => $endUtc,
                'status' => AppointmentStatus::PENDING,
                'cancellation_reason' => null,
            ]);
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
