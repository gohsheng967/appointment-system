<?php

namespace App\Filament\Support;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AppointmentDashboardScope
{
    public function appointmentsQuery(?User $user): Builder
    {
        return Appointment::query()
            ->when(
                $user?->isStaff() ?? false,
                static fn (Builder $query) => $query->where('staff_id', $user->id),
            );
    }

    public function statusHeading(?User $user): string
    {
        return $user?->isStaff() ? 'My Appointment Status' : 'Appointment Status';
    }

    public function trendHeading(?User $user): string
    {
        return $user?->isStaff() ? 'My Bookings Last 7 Days' : 'Bookings Last 7 Days';
    }

    public function canViewBranchBreakdown(?User $user): bool
    {
        return $user?->isAdmin() ?? false;
    }
}
