<?php

namespace App\Domain\Appointments\Services;

use App\Models\Branch;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class BranchOperatingHoursService
{
    public function assertWithinHours(
        Branch $branch,
        CarbonInterface $startUtc,
        CarbonInterface $endUtc,
    ): void {
        $localStart = utc_to_branch_local($startUtc, $branch->timezone);
        $localEnd = utc_to_branch_local($endUtc, $branch->timezone);

        if ($localEnd->lessThanOrEqualTo($localStart)) {
            throw ValidationException::withMessages([
                'start_at' => ['End time must be after start time.'],
            ]);
        }

        if (! $localStart->isSameDay($localEnd)) {
            throw ValidationException::withMessages([
                'start_at' => ['Appointment must start and end on the same local day.'],
            ]);
        }

        $opening = CarbonImmutable::parse(
            $localStart->toDateString().' '.$branch->opening_time,
            $branch->timezone,
        );
        $closing = CarbonImmutable::parse(
            $localStart->toDateString().' '.$branch->closing_time,
            $branch->timezone,
        );

        if ($closing->lessThanOrEqualTo($opening)) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch opening and closing times are invalid.'],
            ]);
        }

        if ($localStart->lt($opening) || $localEnd->gt($closing)) {
            throw ValidationException::withMessages([
                'start_at' => ['Appointment must be fully within branch operating hours.'],
            ]);
        }
    }
}
