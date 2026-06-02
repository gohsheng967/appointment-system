<?php

namespace App\Policies;

use App\Domain\Appointments\Services\AppointmentAuthorizationService;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function __construct(
        private readonly AppointmentAuthorizationService $authorizationService,
    ) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->authorizationService->canViewAny($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canView($user, $appointment);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->authorizationService->canCreate($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canUpdate($user, $appointment);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canDelete($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->authorizationService->canDelete($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canDelete($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function editScheduling(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canEditScheduling($user, $appointment);
    }

    public function transitionStatus(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canTransitionStatus($user, $appointment);
    }

    public function reassignStaff(User $user, Appointment $appointment): bool
    {
        return $this->authorizationService->canReassignStaff($user, $appointment);
    }
}
