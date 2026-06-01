<?php

namespace App\Casts;

use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AppointmentStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): AppointmentStatus
    {
        return AppointmentStatus::tryFrom((string) $value) ?? AppointmentStatus::PENDING;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof AppointmentStatus) {
            return $value->value;
        }

        if (is_string($value) && AppointmentStatus::tryFrom($value)) {
            return $value;
        }

        return AppointmentStatus::PENDING->value;
    }
}

