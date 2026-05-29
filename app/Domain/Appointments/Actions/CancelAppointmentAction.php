<?php

namespace App\Domain\Appointments\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class CancelAppointmentAction
{
    public function __construct(private readonly TransitionAppointmentStatusAction $transitionAction) {}

    public function __invoke(Appointment $appointment, string $reason): Appointment
    {
        return ($this->transitionAction)($appointment, AppointmentStatus::CANCELLED, $reason);
    }
}
