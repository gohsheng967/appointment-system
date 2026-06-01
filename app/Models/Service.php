<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasUuidRouteKey;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'duration_minutes',
        'image',
        'price',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $service): void {
            if ($service->hasActiveAppointments()) {
                throw ValidationException::withMessages([
                    'service' => ['Cannot delete service while it has active appointments (Confirmed or In Progress).'],
                ]);
            }
        });
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function hasActiveAppointments(): bool
    {
        return $this->appointments()
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::IN_PROGRESS->value,
            ])
            ->exists();
    }
}
