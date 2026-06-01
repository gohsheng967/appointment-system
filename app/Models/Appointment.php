<?php

namespace App\Models;

use App\Casts\AppointmentStatusCast;
use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasUuidRouteKey;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, HasUuidRouteKey;

    protected $fillable = [
        'uuid',
        'branch_id',
        'staff_id',
        'customer_id',
        'service_id',
        'start_at',
        'end_at',
        'status',
        'cancellation_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => AppointmentStatusCast::class,
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function getBookingReferenceAttribute(): string
    {
        $timestamp = (string) ($this->created_at?->getTimestamp() ?? now()->getTimestamp());

        return sprintf('APT-%s-%06d', $timestamp, $this->id);
    }

    public function scopeForBranchLocalDate(Builder $query, int $branchId, string $localDate): Builder
    {
        $branch = Branch::query()->find($branchId);

        if (! $branch) {
            return $query;
        }

        $startUtc = branch_local_to_utc($localDate.' 00:00:00', $branch->timezone);
        $endUtc = $startUtc->addDay();

        return $query
            ->where('branch_id', $branch->id)
            ->where('start_at', '>=', $startUtc->toDateTimeString())
            ->where('start_at', '<', $endUtc->toDateTimeString());
    }
}
