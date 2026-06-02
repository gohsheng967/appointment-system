<?php

namespace App\Domain\Appointments\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AppointmentAuthorizationService
{
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $this->canViewAny($user)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isStaff()) {
            return $query->where('staff_id', $user->id);
        }

        return $query;
    }

    public function canViewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function canView(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStaff() && (int) $appointment->staff_id === (int) $user->id;
    }

    public function canCreate(User $user): bool
    {
        return $user->isAdmin();
    }

    public function canUpdate(User $user, Appointment $appointment): bool
    {
        if ($appointment->status->isTerminal()) {
            return false;
        }

        return $this->canTransitionStatus($user, $appointment);
    }

    public function canEditScheduling(User $user, Appointment $appointment): bool
    {
        if ($appointment->status->isTerminal()) {
            return false;
        }

        return $user->isAdmin();
    }

    public function canTransitionStatus(User $user, Appointment $appointment): bool
    {
        return $this->canView($user, $appointment);
    }

    public function canReassignStaff(User $user, Appointment $appointment): bool
    {
        return $this->canView($user, $appointment) && $user->isAdmin();
    }

    public function canDelete(User $user): bool
    {
        return $user->isAdmin();
    }
}
