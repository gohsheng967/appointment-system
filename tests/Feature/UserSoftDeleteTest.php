<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_staff_does_not_delete_appointment_history(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);

        $appointment = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $staff->delete();

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_appointment_staff_relation_still_resolves_when_staff_is_soft_deleted(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T12:00', $branch->timezone);

        $appointment = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $staff->delete();

        $reloaded = Appointment::query()->with('staff')->findOrFail($appointment->id);

        $this->assertNotNull($reloaded->staff);
        $this->assertSame($staff->id, $reloaded->staff->id);
        $this->assertTrue($reloaded->staff->trashed());
    }

    public function test_user_with_active_appointment_cannot_be_soft_deleted(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T14:00', $branch->timezone);

        Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $this->expectException(ValidationException::class);

        $staff->delete();
    }
}
