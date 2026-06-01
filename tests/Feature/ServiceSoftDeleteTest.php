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

class ServiceSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_service_does_not_delete_appointment_history(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T09:00', $branch->timezone);

        $appointment = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $service->delete();

        $this->assertSoftDeleted('services', ['id' => $service->id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_appointment_service_relation_still_resolves_when_service_is_soft_deleted(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T11:00', $branch->timezone);

        $appointment = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $service->delete();

        $reloaded = Appointment::query()->with('service')->findOrFail($appointment->id);

        $this->assertNotNull($reloaded->service);
        $this->assertSame($service->id, $reloaded->service->id);
        $this->assertTrue($reloaded->service->trashed());
    }

    public function test_service_with_active_appointment_cannot_be_soft_deleted(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T15:00', $branch->timezone);

        Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::IN_PROGRESS,
        ]);

        $this->expectException(ValidationException::class);

        $service->delete();
    }
}

