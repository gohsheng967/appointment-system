<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait PreventsDeletionWithActiveAppointments
{
    protected static function bootPreventsDeletionWithActiveAppointments(): void
    {
        static::deleting(function (Model $model): void {
            if (! method_exists($model, 'hasActiveAppointments') || ! $model->hasActiveAppointments()) {
                return;
            }

            throw ValidationException::withMessages([
                $model->activeAppointmentsDeletionValidationKey() => [
                    $model->activeAppointmentsDeletionMessage(),
                ],
            ]);
        });
    }

    abstract protected function activeAppointmentsDeletionMessage(): string;

    abstract protected function activeAppointmentsDeletionValidationKey(): string;
}
