<?php

namespace Tests\Feature;

use App\Domain\Appointments\Actions\CreateAppointmentAction;
use App\Domain\Appointments\Actions\TransitionAppointmentStatusAction;
use App\Domain\Appointments\Actions\UpdateAppointmentAction;
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

    public function test_overlap_blocks_for_completed_status_but_not_no_show(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $first = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        app(TransitionAppointmentStatusAction::class)($first, AppointmentStatus::CONFIRMED);
        app(TransitionAppointmentStatusAction::class)($first, AppointmentStatus::IN_PROGRESS);
        app(TransitionAppointmentStatusAction::class)($first, AppointmentStatus::COMPLETED);

        $this->expectException(ValidationException::class);
        app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:30',
        ]);
    }

    public function test_overlap_allows_booking_when_existing_appointment_is_no_show(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $first = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        app(TransitionAppointmentStatusAction::class)($first, AppointmentStatus::CONFIRMED);
        app(TransitionAppointmentStatusAction::class)($first, AppointmentStatus::NO_SHOW);

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

    public function test_status_flow_matches_business_rules_and_completed_is_terminal(): void
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

        $transition($appointment, AppointmentStatus::CONFIRMED);
        $transition($appointment, AppointmentStatus::IN_PROGRESS);
        $transition($appointment, AppointmentStatus::COMPLETED);

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::COMPLETED, $appointment->status);

        $this->expectException(ValidationException::class);
        $transition($appointment, AppointmentStatus::CANCELLED, 'Too late');
    }

    public function test_pending_can_be_cancelled_directly_with_reason(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $appointment = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        app(TransitionAppointmentStatusAction::class)(
            $appointment,
            AppointmentStatus::CANCELLED,
            'Customer requested cancellation',
        );

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::CANCELLED, $appointment->status);
    }

    public function test_terminal_appointment_cannot_be_rescheduled_or_reassigned(): void
    {
        [$branch, $service, $customer, $staff] = $this->seedBase();

        $appointment = app(CreateAppointmentAction::class)([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        app(TransitionAppointmentStatusAction::class)($appointment, AppointmentStatus::CONFIRMED);
        app(TransitionAppointmentStatusAction::class)($appointment, AppointmentStatus::IN_PROGRESS);
        app(TransitionAppointmentStatusAction::class)($appointment, AppointmentStatus::COMPLETED);

        $otherStaff = User::factory()->staff($branch)->create();

        $this->expectException(ValidationException::class);
        app(UpdateAppointmentAction::class)($appointment, [
            'staff_id' => $otherStaff->id,
            'start_at' => '2026-06-01T12:00',
        ]);
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
