<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasActiveAppointments
{
    abstract public function appointments(): HasMany;

    public function hasActiveAppointments(): bool
    {
        return $this->appointments()
            ->active()
            ->exists();
    }
}
