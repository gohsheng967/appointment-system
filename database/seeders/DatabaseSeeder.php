<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $branch = Branch::query()->firstOrCreate(
            ['name' => 'Kuala Lumpur Central'],
            [
                'timezone' => 'Asia/Kuala_Lumpur',
                'opening_time' => '09:00:00',
                'closing_time' => '18:00:00',
                'address' => 'Jalan Sultan Ismail, Kuala Lumpur',
                'phone' => '+60312345678',
            ],
        );

        $serviceConsultation = Service::query()->firstOrCreate(
            ['name' => 'Consultation'],
            [
                'duration_minutes' => 60,
                'price' => 120,
                'description' => 'General consultation session.',
            ],
        );

        Service::query()->firstOrCreate(
            ['name' => 'Follow-up'],
            [
                'duration_minutes' => 30,
                'price' => 80,
                'description' => 'Follow-up session.',
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'branch_id' => null,
            ],
        );

        $staff = User::query()->firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'role' => UserRole::STAFF,
                'branch_id' => $branch->id,
            ],
        );

        $customer = Customer::query()->firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Sample Customer',
                'phone' => '+60111222333',
            ],
        );

        $startUtc = branch_local_to_utc(
            CarbonImmutable::now($branch->timezone)->addDay()->setTime(10, 0, 0),
            $branch->timezone,
        );

        Appointment::query()->firstOrCreate([
            'branch_id' => $branch->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'service_id' => $serviceConsultation->id,
            'start_at' => $startUtc,
            'end_at' => $startUtc->addMinutes($serviceConsultation->duration_minutes),
            'status' => AppointmentStatus::PENDING,
        ]);
    }
}
