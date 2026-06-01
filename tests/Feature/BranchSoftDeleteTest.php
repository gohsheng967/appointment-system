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

class BranchSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_branch_does_not_delete_appointment_history(): void
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

        $branch->delete();

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_appointment_branch_relation_still_resolves_when_branch_is_soft_deleted(): void
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

        $branch->delete();

        $reloaded = Appointment::query()->with('branch')->findOrFail($appointment->id);

        $this->assertNotNull($reloaded->branch);
        $this->assertSame($branch->id, $reloaded->branch->id);
        $this->assertTrue($reloaded->branch->trashed());
    }

    public function test_branch_with_active_appointment_cannot_be_soft_deleted(): void
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

        $branch->delete();
    }
}

