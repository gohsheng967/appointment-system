<?php

namespace App\Domain\Appointments\Actions;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class AssignAvailableStaffAction
{
    public function __invoke(
        Branch $branch,
        CarbonInterface $startUtc,
        CarbonInterface $endUtc,
    ): User {
        $availableStaff = User::query()
            ->where('role', UserRole::STAFF->value)
            ->where('branch_id', $branch->id)
            ->whereDoesntHave('appointments', function ($query) use ($startUtc, $endUtc): void {
                $query
                    ->whereIn(
                        'status',
                        array_map(
                            static fn (AppointmentStatus $status) => $status->value,
                            AppointmentStatus::blockingStatuses(),
                        ),
                    )
                    ->where('start_at', '<', $endUtc->toDateTimeString())
                    ->where('end_at', '>', $startUtc->toDateTimeString());
            })
            ->orderBy('id')
            ->first();

        if (! $availableStaff) {
            throw ValidationException::withMessages([
                'start_at' => ['No staff member is available at the selected time.'],
            ]);
        }

        return $availableStaff;
    }
}
