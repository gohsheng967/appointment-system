<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_only_view_and_update_own_appointments(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();

        $staffA = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);
        $staffB = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);
        $admin = User::factory()->admin()->create(['role' => UserRole::ADMIN]);

        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);

        $apptForA = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staffA->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::PENDING,
        ]);

        $apptForB = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staffB->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc->copy()->addHours(2),
            'end_at' => $startUtc->copy()->addHours(3),
            'status' => AppointmentStatus::PENDING,
        ]);

        $this->assertTrue($staffA->can('view', $apptForA));
        $this->assertTrue($staffA->can('update', $apptForA));
        $this->assertFalse($staffA->can('view', $apptForB));
        $this->assertFalse($staffA->can('update', $apptForB));

        $this->assertTrue($admin->can('view', $apptForA));
        $this->assertTrue($admin->can('update', $apptForB));
    }

    public function test_staff_listing_scope_only_returns_own_appointments(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();

        $staffA = User::factory()->staff($branch)->create();
        $staffB = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);

        $apptForA = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staffA->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::PENDING,
        ]);

        Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staffB->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc->copy()->addHours(2),
            'end_at' => $startUtc->copy()->addHours(3),
            'status' => AppointmentStatus::PENDING,
        ]);

        $this->actingAs($staffA);

        $ids = AppointmentResource::getEloquentQuery()->pluck('id')->all();

        $this->assertSame([$apptForA->id], $ids);
    }

    public function test_terminal_appointments_cannot_be_updated(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();

        $staff = User::factory()->staff($branch)->create();
        $admin = User::factory()->admin()->create(['role' => UserRole::ADMIN]);

        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);
        $terminalStatuses = [AppointmentStatus::COMPLETED, AppointmentStatus::CANCELLED, AppointmentStatus::NO_SHOW];

        foreach ($terminalStatuses as $status) {
            $appointment = Appointment::query()->create([
                'branch_id' => $branch->id,
                'staff_id' => $staff->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'start_at' => $startUtc,
                'end_at' => $startUtc->copy()->addMinutes(60),
                'status' => $status,
                'cancellation_reason' => $status === AppointmentStatus::CANCELLED ? 'Cancelled' : null,
            ]);

            $this->assertFalse($admin->can('update', $appointment));
            $this->assertFalse($staff->can('update', $appointment));
        }
    }
}
