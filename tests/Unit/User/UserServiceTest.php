<?php

declare(strict_types=1);

namespace App\Tests\Unit\User;

use App\Tests\Support\DatabaseTestCase;
use App\User\UserException;
use App\User\UserRole;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class UserServiceTest extends DatabaseTestCase
{
    public function testCreateHashesThePasswordAndStoresTheRole(): void
    {
        $service = $this->services()->userService();

        $user = $service->create('admin', 'password123', 'Site Administrator', 'admin@example.com', UserRole::Admin);

        assertSame('admin', $user->username);
        assertSame('Site Administrator', $user->displayName);
        assertSame(UserRole::Admin, $user->role);
        assertTrue($user->isActive);
        assertTrue($user->isAdmin());
        assertNotSame('password123', $user->passwordHash);
        assertSame('admin@example.com', $user->email);
    }

    public function testUsernameMustBeUnique(): void
    {
        $service = $this->services()->userService();
        $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Username "admin" is already in use.');

        $service->create('admin', 'password123', 'Other', null, UserRole::Staff);
    }

    public function testEmailMustBeUnique(): void
    {
        $service = $this->services()->userService();
        $service->create('admin', 'password123', 'Admin', 'admin@example.com', UserRole::Admin);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Email "admin@example.com" is already in use.');

        $service->create('other', 'password123', 'Other', 'admin@example.com', UserRole::Staff);
    }

    public function testPasswordTooShortIsRejected(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('The password must be at least 8 characters long.');

        $this->services()->userService()->create('admin', 'short', 'Admin', null, UserRole::Admin);
    }

    public function testAuthenticateAcceptsValidCredentialsAndRejectsOthers(): void
    {
        $service = $this->services()->userService();
        $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        assertSame('admin', $service->authenticate('admin', 'password123')?->username);
        assertNull($service->authenticate('admin', 'wrong-password'));
        assertNull($service->authenticate('nobody', 'password123'));
        assertNull($service->authenticate('admin', ''));
    }

    public function testInactiveUserCannotAuthenticate(): void
    {
        $services = $this->services();
        $service = $services->userService();
        $admin = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $service->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);
        $staffer = $services->users()->findByUsername('staffer');

        assertTrue($staffer !== null);
        $service->setActive($staffer->id, false, $admin->id);

        assertNull($service->authenticate('staffer', 'password123'));
    }

    public function testChangePassword(): void
    {
        $service = $this->services()->userService();
        $user = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $service->changePassword($user->id, 'new-password');

        assertSame($user->id, $service->authenticate('admin', 'new-password')?->id);
        assertNull($service->authenticate('admin', 'password123'));
    }

    public function testUpdateChangesProfileAndRole(): void
    {
        $service = $this->services()->userService();
        $admin = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $user = $service->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);

        $updated = $service->update($user->id, 'Warehouse staff', 'staff@example.com', UserRole::Manager, true, $admin->id);

        assertSame('Warehouse staff', $updated->displayName);
        assertSame(UserRole::Manager, $updated->role);
        assertSame('staff@example.com', $updated->email);
    }

    public function testUpdateRefusesToDeactivateYourself(): void
    {
        $service = $this->services()->userService();
        $admin = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('You cannot deactivate your own account.');

        $service->update($admin->id, 'Admin', null, UserRole::Admin, false, $admin->id);
    }

    public function testUpdateRefusesToChangeYourOwnRole(): void
    {
        $service = $this->services()->userService();
        $admin = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('You cannot change the role of your own account.');

        $service->update($admin->id, 'Admin', null, UserRole::Staff, true, $admin->id);
    }

    public function testLastActiveAdministratorIsProtected(): void
    {
        $services = $this->services();
        $service = $services->userService();
        $admin = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $second = $service->create('second', 'password123', 'Second', null, UserRole::Admin);

        $service->setActive($second->id, false, $admin->id);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('At least one active administrator must remain.');

        $service->setActive($admin->id, false, $second->id);
    }

    public function testDeleteRemovesTheUser(): void
    {
        $services = $this->services();
        $service = $services->userService();
        $admin = $service->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $user = $service->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);

        $service->delete($user->id, $admin->id);

        assertNull($services->users()->findById($user->id));
        assertFalse($services->users()->existsByUsername('staffer'));
    }
}
