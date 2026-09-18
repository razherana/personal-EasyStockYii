<?php

declare(strict_types=1);

namespace App\User;

/**
 * Roles a user account can have. Permissions per role are configured in `config/common/params.php`.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Manager => 'Manager',
            self::Staff => 'Staff',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
