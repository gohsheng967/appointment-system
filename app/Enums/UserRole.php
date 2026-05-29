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
            self::ADMIN->value => 'Admin',
            self::STAFF->value => 'Staff',
        ];
    }
}
