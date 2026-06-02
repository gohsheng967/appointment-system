<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentActiveScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_only_includes_confirmed_and_in_progress_appointments(): void
    {
        $branch = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();
        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);

        $statuses = [
            AppointmentStatus::PENDING,
            AppointmentStatus::CONFIRMED,
            AppointmentStatus::IN_PROGRESS,
            AppointmentStatus::COMPLETED,
            AppointmentStatus::CANCELLED,
        ];

        foreach ($statuses as $index => $status) {
            Appointment::query()->create([
                'branch_id' => $branch->id,
                'staff_id' => $staff->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'start_at' => $startUtc->addHours($index),
                'end_at' => $startUtc->addHours($index + 1),
                'status' => $status,
            ]);
        }

        $this->assertSame(2, Appointment::query()->active()->count());
    }
}
