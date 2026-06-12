<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasActiveAppointments;
use App\Models\Concerns\HasUuidRouteKey;
use App\Models\Concerns\PreventsDeletionWithActiveAppointments;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasActiveAppointments, HasFactory, Notifiable, HasUuidRouteKey, PreventsDeletionWithActiveAppointments, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'start_working_time',
        'end_working_time',

    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    protected function activeAppointmentsDeletionMessage(): string
    {
        return 'Cannot delete user while they have active appointments (Confirmed or In Progress).';
    }

    protected function activeAppointmentsDeletionValidationKey(): string
    {
        return 'user';
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::STAFF;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::STAFF], true);
    }
}
