<?php

declare(strict_types=1);

namespace App\Access;

use App\User\UserRole;

use function in_array;

/**
 * Maps roles to their permissions, as configured in `config/common/params.php`.
 */
final readonly class RolePermissions
{
    /**
     * @param array<string, list<string>> $permissions Role name to permission name list.
     */
    public function __construct(
        private array $permissions,
    ) {}

    public function has(UserRole $role, Permission $permission): bool
    {
        return in_array($permission->value, $this->permissions[$role->value] ?? [], true);
    }

    /**
     * @return list<Permission>
     */
    public function all(UserRole $role): array
    {
        $granted = [];

        foreach (Permission::cases() as $permission) {
            if ($this->has($role, $permission)) {
                $granted[] = $permission;
            }
        }

        return $granted;
    }
}
