<?php

namespace App\Models;

use App\Models\Concerns\HasActiveAppointments;
use App\Models\Concerns\HasUuidRouteKey;
use App\Models\Concerns\PreventsDeletionWithActiveAppointments;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasActiveAppointments, HasFactory, HasUuidRouteKey, PreventsDeletionWithActiveAppointments, SoftDeletes;

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

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    protected function activeAppointmentsDeletionMessage(): string
    {
        return 'Cannot delete service while it has active appointments (Confirmed or In Progress).';
    }

    protected function activeAppointmentsDeletionValidationKey(): string
    {
        return 'service';
    }
}
