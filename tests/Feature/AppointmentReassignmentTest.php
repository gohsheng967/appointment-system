<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentReassignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_reassign_action_is_disabled_when_no_other_staff_is_available(): void
    {
        [$admin, $appointment, $busyStaff] = $this->seedReassignmentScenario();

        Appointment::query()->create([
            'branch_id' => $appointment->branch_id,
            'staff_id' => $busyStaff->id,
            'customer_id' => Customer::factory()->create()->id,
            'service_id' => $appointment->service_id,
            'start_at' => $appointment->start_at,
            'end_at' => $appointment->end_at,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListAppointments::class)
            ->assertTableActionDisabled('reassign_staff', $appointment->getKey());
    }

    public function test_admin_can_reassign_appointment_to_available_staff_from_table_action(): void
    {
        [$admin, $appointment, $availableStaff] = $this->seedReassignmentScenario();

        $this->actingAs($admin);

        Livewire::test(ListAppointments::class)
            ->callTableAction('reassign_staff', $appointment->getKey(), [
                'staff_id' => $availableStaff->id,
            ])
            ->assertHasNoTableActionErrors();

        $appointment->refresh();

        $this->assertSame($availableStaff->id, $appointment->staff_id);
    }

    public function test_reassign_action_is_disabled_when_appointment_is_not_confirmed(): void
    {
        [$admin, $appointment] = $this->seedReassignmentScenario();

        $appointment->update([
            'status' => AppointmentStatus::IN_PROGRESS,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListAppointments::class)
            ->assertTableActionDisabled('reassign_staff', $appointment->getKey());
    }

    /**
     * @return array{User, Appointment, User}
     */
    private function seedReassignmentScenario(): array
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create([
            'duration_minutes' => 60,
        ]);

        $admin = User::factory()->admin()->create(['role' => UserRole::ADMIN]);
        $currentStaff = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);
        $otherStaff = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);

        $startUtc = branch_local_to_utc('2026-06-09T15:00', $branch->timezone);

        $appointment = Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $currentStaff->id,
            'customer_id' => Customer::factory()->create()->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes($service->duration_minutes),
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        return [$admin, $appointment, $otherStaff];
    }
}
