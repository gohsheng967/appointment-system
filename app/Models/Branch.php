<?php

namespace App\Models;

use App\Models\Concerns\HasActiveAppointments;
use App\Models\Concerns\HasUuidRouteKey;
use App\Models\Concerns\PreventsDeletionWithActiveAppointments;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasActiveAppointments, HasFactory, HasUuidRouteKey, PreventsDeletionWithActiveAppointments, SoftDeletes;

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

    protected function activeAppointmentsDeletionMessage(): string
    {
        return 'Cannot delete branch while it has active appointments (Confirmed or In Progress).';
    }

    protected function activeAppointmentsDeletionValidationKey(): string
    {
        return 'branch';
    }
}
