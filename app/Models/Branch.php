<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasUuidRouteKey;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'timezone',
        'opening_time',
        'closing_time',
        'address',
        'phone_number',
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
        static::deleting(function (self $branch): void {
            if ($branch->hasActiveAppointments()) {
                throw ValidationException::withMessages([
                    'branch' => ['Cannot delete branch while it has active appointments (Confirmed or In Progress).'],
                ]);
            }
        });
    }

    /**
     * @return HasMany<User, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
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
