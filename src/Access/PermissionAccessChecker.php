<?php

declare(strict_types=1);

namespace App\Access;

use App\User\UserRepository;
use Stringable;
use Yiisoft\Access\AccessCheckerInterface;

use function is_numeric;

/**
 * Checks permissions of the current user against the configured role to permission map.
 *
 * The user package calls this through `CurrentUser::can()`.
 */
final readonly class PermissionAccessChecker implements AccessCheckerInterface
{
    public function __construct(
        private UserRepository $users,
        private RolePermissions $permissions,
    ) {}

    public function userHasPermission(
        int|string|Stringable|null $userId,
        string $permissionName,
        array $parameters = [],
    ): bool {
        $permission = Permission::tryFrom($permissionName);

        if ($permission === null || !is_numeric((string) $userId)) {
            return false;
        }

        $user = $this->users->findById((int) (string) $userId);

        if ($user === null || !$user->isActive) {
            return false;
        }

        return $this->permissions->has($user->role, $permission);
    }
}
