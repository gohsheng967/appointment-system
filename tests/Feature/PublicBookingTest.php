<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_creates_appointment_and_auto_assigns_staff(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $staff = User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);

        $response = $this->post(route('booking.store'), [
            'name' => 'Public Customer',
            'email' => 'public@example.com',
            'phone' => '',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $response->assertRedirect(route('booking.success'));
        $response->assertSessionHas('booking_reference');

        $appointment = Appointment::query()->first();
        $this->assertNotNull($appointment);
        $this->assertSame($staff->id, $appointment->staff_id);
        $this->assertSame(AppointmentStatus::PENDING, $appointment->status);
    }

    public function test_customer_is_reused_by_email_or_phone(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 30]);
        User::factory()->staff($branch)->create();

        $payload = [
            'name' => 'Repeat Customer',
            'email' => 'repeat@example.com',
            'phone' => '+60112223333',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
        ];

        $this->post(route('booking.store'), $payload + ['start_at' => '2026-06-01T10:00']);
        $this->post(route('booking.store'), $payload + ['start_at' => '2026-06-01T11:00']);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_booking_fails_when_no_staff_is_available(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $customer = Customer::factory()->create();
        $staff = User::factory()->staff($branch)->create();

        $startUtc = branch_local_to_utc('2026-06-01T10:00', $branch->timezone);
        Appointment::query()->create([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->copy()->addMinutes(60),
            'status' => AppointmentStatus::PENDING,
        ]);

        $response = $this->from(route('booking.create'))->post(route('booking.store'), [
            'name' => 'Blocked Customer',
            'email' => 'blocked@example.com',
            'phone' => '',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $response->assertRedirect(route('booking.create'));
        $response->assertSessionHasErrors(['start_at']);
    }
}
