<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_creates_pending_appointment_without_auto_assigning_staff(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);

        $response = $this->post(route('booking.store'), [
            'name' => 'Public Customer',
            'email' => 'public@example.com',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $response->assertRedirect(route('booking.success'));
        $response->assertSessionHas('booking_reference', static function (?string $reference): bool {
            if (! is_string($reference)) {
                return false;
            }

            return preg_match('/^APT-\d+-\d{6}$/', $reference) === 1;
        });

        $appointment = Appointment::query()->first();
        $this->assertNotNull($appointment);
        $this->assertNull($appointment->staff_id);
        $this->assertSame(AppointmentStatus::PENDING, $appointment->status);
    }

    public function test_customer_is_reused_when_email_and_phone_match_same_record(): void
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
            'phone_country_code' => '+60',
            'phone_number' => '112223333',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
        ];

        $this->post(route('booking.store'), $payload + ['start_at' => '2026-06-01T10:00']);
        $firstAppointment = Appointment::query()->first();
        $this->assertNotNull($firstAppointment);
        $firstAppointment->update([
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => 'Customer cancelled',
        ]);

        $this->post(route('booking.store'), $payload + ['start_at' => '2026-06-01T11:00']);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_customer_is_created_new_when_email_and_phone_match_different_records(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 30]);
        User::factory()->staff($branch)->create();

        $emailOwner = Customer::factory()->create([
            'name' => 'Email Owner',
            'email' => 'strict-match@example.com',
            'phone_number' => '+60111111111',
        ]);
        $phoneOwner = Customer::factory()->create([
            'name' => 'Phone Owner',
            'email' => 'someone-else@example.com',
            'phone_number' => '+60122222222',
        ]);

        $response = $this->post(route('booking.store'), [
            'name' => 'New Composite Customer',
            'email' => 'strict-match@example.com',
            'phone_country_code' => '+60',
            'phone_number' => '122222222',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $response->assertRedirect(route('booking.success'));

        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseHas('customers', [
            'name' => 'Email Owner',
            'id' => $emailOwner->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'name' => 'Phone Owner',
            'id' => $phoneOwner->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'name' => 'New Composite Customer',
            'email' => 'strict-match@example.com',
            'phone_number' => '+60122222222',
        ]);
    }

    public function test_public_booking_still_creates_when_staff_are_busy_because_assignment_happens_later(): void
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
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'start_at' => '2026-06-01T10:00',
        ]);

        $response->assertRedirect(route('booking.success'));
        $this->assertDatabaseCount('appointments', 2);

        $newAppointment = Appointment::query()->latest('id')->first();
        $this->assertNotNull($newAppointment);
        $this->assertNull($newAppointment->staff_id);
        $this->assertSame(AppointmentStatus::PENDING, $newAppointment->status);
    }

    public function test_public_booking_blocks_when_customer_already_has_ongoing_booking(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);

        $payload = [
            'name' => 'Repeat Ongoing Customer',
            'email' => 'repeat-ongoing@example.com',
            'phone_country_code' => '+60',
            'phone_number' => '123456789',
            'branch_id' => $branch->id,
            'service_id' => $service->id,
        ];

        $this->post(route('booking.store'), $payload + ['start_at' => '2026-06-01T10:00'])
            ->assertRedirect(route('booking.success'));

        $response = $this->from(route('booking.create'))
            ->post(route('booking.store'), $payload + ['start_at' => '2026-06-01T12:00']);

        $response->assertRedirect(route('booking.create'));
        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_public_booking_blocks_past_time_on_same_day_in_branch_timezone(): void
    {
        $branch = Branch::factory()->create([
            'timezone' => 'Asia/Kuala_Lumpur',
            'opening_time' => '09:00:00',
            'closing_time' => '18:00:00',
        ]);
        $service = Service::factory()->create(['duration_minutes' => 60]);
        User::factory()->staff($branch)->create(['role' => UserRole::STAFF]);

        $frozenNow = CarbonImmutable::parse('2026-06-01 10:30:00', $branch->timezone);
        Carbon::setTestNow($frozenNow);
        CarbonImmutable::setTestNow($frozenNow);

        try {
            $response = $this->from(route('booking.create'))->post(route('booking.store'), [
                'name' => 'Past Time Customer',
                'email' => 'past-time@example.com',
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'start_at' => '2026-06-01T10:00',
            ]);

            $response->assertRedirect(route('booking.create'));
            $response->assertSessionHasErrors('start_at');
            $this->assertDatabaseCount('appointments', 0);
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }
    }
}
