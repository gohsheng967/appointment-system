<?php

namespace App\Domain\Appointments\Services;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AppointmentSchedulingResolver
{
    public function __construct(
        private readonly BranchOperatingHoursService $operatingHoursService,
        private readonly AppointmentOverlapService $overlapService,
        private readonly CustomerOngoingBookingLimitService $customerOngoingBookingLimitService,
    ) {}

    /**
     * @param  array{
     *   branch_id: int,
     *   service_id: int,
     *   customer_id: int,
     *   start_at: string,
     *   staff_id?: int|null
     * }  $payload
     * @return array{
     *   branch: Branch,
     *   service: Service,
     *   customer: Customer,
     *   staff: User|null,
     *   startUtc: CarbonImmutable,
     *   endUtc: CarbonImmutable
     * }
     */
    public function resolveForCreate(array $payload): array
    {
        $branch = Branch::query()->findOrFail((int) $payload['branch_id']);
        $service = Service::query()->findOrFail((int) $payload['service_id']);
        $customer = Customer::query()->findOrFail((int) $payload['customer_id']);

        $startUtc = branch_local_to_utc((string) $payload['start_at'], $branch->timezone);
        $endUtc = $startUtc->addMinutes($service->duration_minutes);

        $this->assertStartNotInPast($branch, $startUtc);
        $this->operatingHoursService->assertWithinHours($branch, $startUtc, $endUtc);

        $staffId = array_key_exists('staff_id', $payload) && filled($payload['staff_id'])
            ? (int) $payload['staff_id']
            : null;

        $staff = $staffId !== null ? User::query()->findOrFail($staffId) : null;

        if ($staff) {
            $this->assertStaffIsAssignableToBranch($staff, $branch);
            $this->overlapService->assertNoOverlap($staff->id, $startUtc, $endUtc);
        }

        $this->customerOngoingBookingLimitService->assertCustomerCanHaveAnotherOngoingBooking($customer->id);

        return [
            'branch' => $branch,
            'service' => $service,
            'customer' => $customer,
            'staff' => $staff,
            'startUtc' => $startUtc,
            'endUtc' => $endUtc,
        ];
    }

    /**
     * @param  array{
     *   branch_id?: int,
     *   service_id?: int,
     *   customer_id?: int,
     *   staff_id?: int|null,
     *   start_at?: string
     * }  $payload
     * @param  bool  $enforceOngoingCustomerBookingLimit
     * @return array{
     *   branch: Branch,
     *   service: Service,
     *   customer: Customer,
     *   staff: User,
     *   startUtc: CarbonImmutable,
     *   endUtc: CarbonImmutable
     * }
     */
    public function resolveForUpdate(
        Appointment $appointment,
        array $payload,
        bool $enforceOngoingCustomerBookingLimit = true,
    ): array
    {
        $branch = Branch::query()->findOrFail((int) ($payload['branch_id'] ?? $appointment->branch_id));
        $service = Service::query()->findOrFail((int) ($payload['service_id'] ?? $appointment->service_id));
        $customer = Customer::query()->findOrFail((int) ($payload['customer_id'] ?? $appointment->customer_id));

        $rawStaffId = array_key_exists('staff_id', $payload)
            ? $payload['staff_id']
            : $appointment->staff_id;

        if (! filled($rawStaffId)) {
            throw ValidationException::withMessages([
                'staff_id' => ['Please assign staff before saving appointment changes.'],
            ]);
        }

        $staff = User::query()->findOrFail((int) $rawStaffId);

        $startUtc = isset($payload['start_at'])
            ? branch_local_to_utc((string) $payload['start_at'], $branch->timezone)
            : $appointment->start_at->toImmutable();
        $endUtc = $startUtc->addMinutes($service->duration_minutes);

        if (isset($payload['start_at'])) {
            $this->assertStartNotInPast($branch, $startUtc);
        }

        $this->assertStaffIsAssignableToBranch($staff, $branch);
        $this->operatingHoursService->assertWithinHours($branch, $startUtc, $endUtc);
        $this->overlapService->assertNoOverlap($staff->id, $startUtc, $endUtc, $appointment->id);

        if ($enforceOngoingCustomerBookingLimit) {
            $this->customerOngoingBookingLimitService->assertCustomerCanHaveAnotherOngoingBooking(
                $customer->id,
                $appointment->id,
            );
        }

        return [
            'branch' => $branch,
            'service' => $service,
            'customer' => $customer,
            'staff' => $staff,
            'startUtc' => $startUtc,
            'endUtc' => $endUtc,
        ];
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

    private function assertStartNotInPast(Branch $branch, CarbonImmutable $startUtc): void
    {
        $localStart = utc_to_branch_local($startUtc, $branch->timezone);
        $localNow = now($branch->timezone);

        if ($localStart->lessThanOrEqualTo($localNow)) {
            throw ValidationException::withMessages([
                'start_at' => ['Appointment start time must be in the future.'],
            ]);
        }
    }
}
