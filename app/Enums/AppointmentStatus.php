<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::CONFIRMED->value => 'Confirmed',
            self::IN_PROGRESS->value => 'In Progress',
            self::COMPLETED->value => 'Completed',
            self::CANCELLED->value => 'Cancelled',
            self::NO_SHOW->value => 'No Show',
        ];
    }

    /**
     * @return list<self>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::IN_PROGRESS,
            self::COMPLETED,
        ];
    }

    public function blocksAvailability(): bool
    {
        return in_array($this, self::blockingStatuses(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::NO_SHOW,
        ], true);
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::PENDING => in_array($target, [self::CONFIRMED, self::CANCELLED], true),
            self::CONFIRMED => in_array($target, [self::IN_PROGRESS, self::CANCELLED, self::NO_SHOW], true),
            self::IN_PROGRESS => $target === self::COMPLETED,
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }
}
