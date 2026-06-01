<?php

namespace Tests\Feature;

use App\Domain\Users\Actions\UpdateUserAction;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserUpdateRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_with_active_appointments_cannot_change_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branchA)->create();

        $startUtc = branch_local_to_utc('2026-06-01T11:00', $branchA->timezone);
        Appointment::query()->create([
            'branch_id' => $branchA->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateUserAction::class)($staff, [
            'branch_id' => $branchB->id,
        ]);
    }

    public function test_staff_without_active_appointments_can_change_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $staff = User::factory()->staff($branchA)->create();

        app(UpdateUserAction::class)($staff, [
            'branch_id' => $branchB->id,
        ]);

        $this->assertSame($branchB->id, $staff->fresh()->branch_id);
    }
}
