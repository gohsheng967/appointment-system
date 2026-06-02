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
            self::PENDING->value => self::PENDING->label(),
            self::CONFIRMED->value => self::CONFIRMED->label(),
            self::IN_PROGRESS->value => self::IN_PROGRESS->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
            self::NO_SHOW->value => self::NO_SHOW->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::CONFIRMED => 'info',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'slate',
        };
    }

    public static function labelFor(self|string|null $status): string
    {
        if ($status instanceof self) {
            return $status->label();
        }

        if (is_string($status)) {
            return self::tryFrom($status)?->label() ?? ucwords(str_replace('_', ' ', $status));
        }

        return '';
    }

    public static function colorFor(self|string|null $status): string
    {
        if ($status instanceof self) {
            return $status->filamentColor();
        }

        if (is_string($status)) {
            return self::tryFrom($status)?->filamentColor() ?? 'gray';
        }

        return 'gray';
    }

    /**
     * @return list<self>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::COMPLETED,
        ];
    }

    /**
     * @return list<self>
     */
    public static function ongoingStatuses(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::IN_PROGRESS,
        ];
    }

    public function blocksAvailability(): bool
    {
        return in_array($this, self::blockingStatuses(), true);
    }

    public function isOngoing(): bool
    {
        return in_array($this, self::ongoingStatuses(), true);
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

    /**
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => $status !== $this && $this->canTransitionTo($status),
        ));
    }

    /**
     * @return array<string, string>
     */
    public function nextOptions(): array
    {
        return collect($this->nextStatuses())
            ->mapWithKeys(static fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
