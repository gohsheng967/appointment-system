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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $branches = [
            Branch::query()->firstOrCreate(
                ['name' => 'Kuala Lumpur Central'],
                [
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'opening_time' => '09:00:00',
                    'closing_time' => '18:00:00',
                    'address' => 'Jalan Sultan Ismail, Kuala Lumpur',
                    'phone_number' => '+60312345678',
                ],
            ),
            Branch::query()->firstOrCreate(
                ['name' => 'Petaling Jaya HQ'],
                [
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'opening_time' => '09:00:00',
                    'closing_time' => '18:00:00',
                    'address' => 'Jalan SS2, Petaling Jaya',
                    'phone_number' => '+60387654321',
                ],
            ),
        ];

        $services = [
            Service::query()->firstOrCreate(
                ['name' => 'Consultation'],
                ['duration_minutes' => 60, 'price' => 120, 'description' => 'General consultation session.'],
            ),
            Service::query()->firstOrCreate(
                ['name' => 'Follow-up'],
                ['duration_minutes' => 30, 'price' => 80, 'description' => 'Follow-up session.'],
            ),
            Service::query()->firstOrCreate(
                ['name' => 'Therapy Session'],
                ['duration_minutes' => 90, 'price' => 220, 'description' => 'Therapy and treatment session.'],
            ),
        ];

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'branch_id' => null,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'ops.admin@example.com'],
            [
                'name' => 'Operations Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'branch_id' => null,
            ],
        );

        $staffMembers = [];
        foreach ($branches as $branchIndex => $branch) {
            for ($i = 1; $i <= 4; $i++) {
                $staffMembers[] = User::query()->firstOrCreate(
                    ['email' => "staff{$branchIndex}{$i}@example.com"],
                    [
                        'name' => "Staff {$branchIndex}{$i}",
                        'password' => Hash::make('password'),
                        'role' => UserRole::STAFF,
                        'branch_id' => $branch->id,
                    ],
                );
            }
        }

        $customers = [];
        for ($i = 1; $i <= 40; $i++) {
            $customers[] = Customer::query()->firstOrCreate(
                ['email' => sprintf('customer%03d@example.com', $i)],
                [
                    'name' => "Sample Customer {$i}",
                    'phone_number' => '+60'.sprintf('11%07d', $i),
                ],
            );
        }

        $slotHours = [9, 10, 11, 13, 14, 15, 16];
        $dayOffsets = [-3, -2, -1, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $statusCycle = [
            AppointmentStatus::PENDING,
            AppointmentStatus::CONFIRMED,
            AppointmentStatus::COMPLETED,
            AppointmentStatus::IN_PROGRESS,
        ];
        $unsignedHash = static fn (string $value): int => (int) sprintf('%u', crc32($value));
        $branchesById = collect($branches)->keyBy('id');

        foreach ($staffMembers as $staff) {
            /** @var Branch $branch */
            $branch = $branchesById->get($staff->branch_id);

            foreach ($dayOffsets as $dayOffset) {
                foreach ($slotHours as $hour) {
                    $seed = "{$staff->id}|{$dayOffset}|{$hour}";

                    $service = $services[$unsignedHash($seed.'|service') % count($services)];
                    $customer = $customers[$unsignedHash($seed.'|customer') % count($customers)];
                    $status = $statusCycle[$unsignedHash($seed.'|status') % count($statusCycle)];

                    $startUtc = branch_local_to_utc(
                        CarbonImmutable::now($branch->timezone)->addDays($dayOffset)->setTime($hour, 0, 0),
                        $branch->timezone,
                    );
                    $endUtc = $startUtc->addMinutes($service->duration_minutes);

                    Appointment::query()->updateOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'staff_id' => $staff->id,
                            'start_at' => $startUtc,
                        ],
                        [
                            'customer_id' => $customer->id,
                            'service_id' => $service->id,
                            'end_at' => $endUtc,
                            'status' => $status,
                            'cancellation_reason' => null,
                        ],
                    );
                }
            }
        }

        // Keep cancelled / no-show samples small (only a few rows).
        $seededStaffIds = collect($staffMembers)->pluck('id')->all();
        $pastScoped = Appointment::query()
            ->whereIn('staff_id', $seededStaffIds)
            ->where('start_at', '<', now('UTC'))
            ->orderBy('start_at')
            ->orderBy('id');

        $cancelledIds = (clone $pastScoped)->limit(4)->pluck('id')->all();
        if ($cancelledIds !== []) {
            Appointment::query()
                ->whereIn('id', $cancelledIds)
                ->update([
                    'status' => AppointmentStatus::CANCELLED,
                    'cancellation_reason' => 'Customer requested cancellation.',
                ]);
        }

        $noShowIds = (clone $pastScoped)
            ->whereNotIn('id', $cancelledIds)
            ->limit(4)
            ->pluck('id')
            ->all();
        if ($noShowIds !== []) {
            Appointment::query()
                ->whereIn('id', $noShowIds)
                ->update([
                    'status' => AppointmentStatus::NO_SHOW,
                    'cancellation_reason' => null,
                ]);
        }

        $this->backfillMissingUuids();
    }

    private function backfillMissingUuids(): void
    {
        foreach ([Branch::class, Service::class, Customer::class, User::class, Appointment::class] as $modelClass) {
            $modelClass::query()
                ->whereNull('uuid')
                ->orWhere('uuid', '')
                ->each(static function ($model): void {
                    $model->forceFill(['uuid' => (string) Str::uuid()])->save();
                });
        }
    }
}
