<?php

declare(strict_types=1);

namespace App\Tests\Unit\Access;

use App\Access\Permission;
use App\Access\RolePermissions;
use App\User\UserRole;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class RolePermissionsTest extends Unit
{
    private function permissions(): RolePermissions
    {
        return new RolePermissions([
            'admin' => ['product:view', 'product:manage', 'stock:view', 'user:manage'],
            'manager' => ['product:view', 'stock:view'],
            'staff' => ['stock:view'],
        ]);
    }

    public function testAdminHasEveryConfiguredPermission(): void
    {
        $permissions = $this->permissions();

        assertTrue($permissions->has(UserRole::Admin, Permission::UserManage));
        assertTrue($permissions->has(UserRole::Admin, Permission::ProductManage));
    }

    public function testManagerCannotManageUsers(): void
    {
        assertFalse($this->permissions()->has(UserRole::Manager, Permission::UserManage));
    }

    public function testStaffCanOnlyViewStock(): void
    {
        $permissions = $this->permissions();

        assertTrue($permissions->has(UserRole::Staff, Permission::StockView));
        assertFalse($permissions->has(UserRole::Staff, Permission::ProductManage));
        assertFalse($permissions->has(UserRole::Staff, Permission::Import));
    }

    public function testRoleWithoutConfigurationHasNoPermission(): void
    {
        $permissions = new RolePermissions([]);

        assertFalse($permissions->has(UserRole::Admin, Permission::ProductView));
        assertSame([], $permissions->all(UserRole::Admin));
    }

    public function testAllReturnsGrantedPermissions(): void
    {
        $granted = $this->permissions()->all(UserRole::Manager);

        assertSame([Permission::ProductView, Permission::StockView], $granted);
    }
}
