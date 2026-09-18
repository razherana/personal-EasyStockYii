<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access;

use App\Access\Permission;
use App\Access\PermissionAccessChecker;
use App\Access\RolePermissions;
use App\Tests\Support\DatabaseTestCase;
use App\User\UserRole;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

final class PermissionAccessCheckerTest extends DatabaseTestCase
{
    private function checker(): PermissionAccessChecker
    {
        return new PermissionAccessChecker(
            $this->services()->users(),
            new RolePermissions([
                'admin' => ['user:manage', 'product:view'],
                'staff' => ['product:view'],
            ]),
        );
    }

    public function testGuestHasNoPermission(): void
    {
        assertFalse($this->checker()->userHasPermission(null, Permission::ProductView->value));
    }

    public function testUserWithoutThatPermissionIsRejected(): void
    {
        $user = $this->services()->userService()->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);

        assertFalse($this->checker()->userHasPermission($user->id, Permission::UserManage->value));
        assertTrue($this->checker()->userHasPermission($user->id, Permission::ProductView->value));
    }

    public function testAdminHasPermission(): void
    {
        $user = $this->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        assertTrue($this->checker()->userHasPermission($user->id, Permission::UserManage->value));
    }

    public function testInactiveUserHasNoPermission(): void
    {
        $services = $this->services();
        $admin = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $user = $services->userService()->create('other', 'password123', 'Other', null, UserRole::Admin);

        $services->userService()->setActive($user->id, false, $admin->id);

        assertFalse($this->checker()->userHasPermission($user->id, Permission::UserManage->value));
    }

    public function testUnknownPermissionAndUnknownUserAreRejected(): void
    {
        $checker = $this->checker();

        assertFalse($checker->userHasPermission(12345, Permission::UserManage->value));
        assertFalse($checker->userHasPermission('not-a-number', Permission::UserManage->value));
        assertFalse($checker->userHasPermission(1, 'unknown:permission'));
    }
}
