<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::now('UTC')->addDay()->setTime(9, 0);

        return [
            'branch_id' => Branch::factory(),
            'staff_id' => User::factory()->staff(),
            'customer_id' => Customer::factory(),
            'service_id' => Service::factory(),
            'start_at' => $start,
            'end_at' => $start->addMinutes(60),
            'status' => AppointmentStatus::PENDING,
            'cancellation_reason' => null,
        ];
    }
}
