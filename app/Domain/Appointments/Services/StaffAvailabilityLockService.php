<?php

namespace App\Domain\Appointments\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAvailabilityLockService
{
    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function forStaff(int $staffId, Closure $callback, int $timeoutSeconds = 5): mixed
    {
        $lockName = "appointments:staff:{$staffId}";

        $acquired = DB::selectOne('SELECT GET_LOCK(?, ?) AS lock_acquired', [
            $lockName,
            $timeoutSeconds,
        ]);

        if (! $acquired || (int) ($acquired->lock_acquired ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'staff_id' => ['Another booking update is in progress for this staff member. Please retry.'],
            ]);
        }

        try {
            return $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }
}

