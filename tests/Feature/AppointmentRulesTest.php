<?php

namespace Tests\Feature;

use App\Domain\Appointments\Actions\CreateAppointmentAction;
use App\Domain\Appointments\Actions\TransitionAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_time_is_derived_from_service_duration(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $appointment = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $this->assertSame(AppointmentStatus::PENDING, $appointment->status);
        $this->assertTrue(
            $appointment->end_at->equalTo($appointment->start_at->copy()->addMinutes($service->duration_minutes)),
        );
    }

    public function test_appointment_must_be_within_branch_operating_hours(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $this->expectException(ValidationException::class);

        app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T17:30',
        ]);
    }

    public function test_staff_must_belong_to_selected_branch(): void
    {
        [$branch, $service, $customer] = $this->seedBase(withStaff: false);

        $otherBranch = Branch::factory()->create();
        $staffFromOtherBranch = User::factory()->staff($otherBranch)->create();

        $this->expectException(ValidationException::class);

        app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staffFromOtherBranch->id,
            'start_at' => '2026-06-01T10:00',
        ]);
    }

    public function test_overlap_blocks_for_active_statuses_but_not_cancelled(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $first = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        try {
            app(CreateAppointmentAction::class)([
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'staff_id' => $staff->id,
                'start_at' => '2026-06-01T10:30',
            ]);

            $this->fail('Expected overlap validation error.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $first->update([
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => 'Customer cancelled',
        ]);

        $second = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:30',
        ]);

        $this->assertInstanceOf(Appointment::class, $second);
    }

    public function test_invalid_status_transition_and_cancel_reason_requirements_are_enforced(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $appointment = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $transition = app(TransitionAppointmentStatusAction::class);

        $this->expectException(ValidationException::class);
        $transition($appointment, AppointmentStatus::COMPLETED);
    }

    public function test_cancelled_status_requires_reason(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $appointment = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $this->expectException(ValidationException::class);

        app(TransitionAppointmentStatusAction::class)($appointment, AppointmentStatus::CANCELLED);
    }

    /**
     * @return array{Branch, Service, Customer, User|null}
     */
    private function seedBase(bool $withStaff = true): array
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);

        $service = Service::factory()->create([
            'duration_minutes' => 60,
        ]);

        $customer = Customer::factory()->create();

        $staff = $withStaff
            ? User::factory()->staff($branch)->create(['role' => UserRole::STAFF])
            : null;

        return [$branch, $service, $customer, $staff];
    }
}
