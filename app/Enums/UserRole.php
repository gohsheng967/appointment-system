<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case STAFF = 'staff';

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this === self::STAFF;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::ADMIN->value => self::ADMIN->label(),
            self::STAFF->value => self::STAFF->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::STAFF => 'Staff',
        };
    }

    public static function labelFor(self|string|null $role): string
    {
        if ($role instanceof self) {
            return $role->label();
        }

        if (is_string($role)) {
            return self::tryFrom($role)?->label() ?? ucfirst($role);
        }

        return '';
    }
}
