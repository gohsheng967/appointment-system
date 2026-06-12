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

        $starttime = $startUtc->toDateTimeString();
        $endtime = $endUtc->toDateTimeString();

        $availableStaff = User::query()
            ->where('role', UserRole::STAFF->value)
            ->where('branch_id', $branch->id)
            ->whereDoesntHave('appointments', function ($query) use ($starttime, $endtime): void {
                $query
                    ->whereIn(
                        'status',
                        array_map(
                            static fn (AppointmentStatus $status) => $status->value,
                            AppointmentStatus::blockingStatuses(),
                        ),
                    )
                    ->where('start_at', '<', $endtime)
                    ->where('end_at', '>', $starttime);
            })
            ->where(function($q) use ($starttime, $endtime) {
                $checkWorkingTimeConfigure = $q->start_working_time && $end_working_time;
                return $q->when($checkWorkingTimeConfigure, function($sq) use ($starttime, $endtime) {
                    return $sq->where('start_working_time', '<', $endtime)
                                ->where('end_working_time', '>', $starttime);
                });
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
