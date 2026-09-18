<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class UsersCest
{
    public function adminSeesTheUserList(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Site Administrator', null, UserRole::Admin);
        $I->services()->userService()->create('staffer', 'password123', 'Staff', null, UserRole::Staff);

        $browser = $I->loginAsAdmin();
        $body = $browser->body($browser->get('/users'));

        assertStringContainsString('staffer', $body);
        assertStringContainsString('Administrator', $body);
        assertStringContainsString('you', $body);
    }

    public function staffCannotOpenUserManagement(FunctionalTester $I): void
    {
        $I->services()->userService()->create('staffer', 'password123', 'Staff', null, UserRole::Staff);

        $browser = $I->browser();
        $browser->login('staffer', 'password123');

        assertSame(403, $browser->status($browser->get('/users')));
        assertSame(403, $browser->status($browser->get('/users/create')));
    }

    public function adminCreatesAUser(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/users/create', [
            'username' => 'warehouse',
            'password' => 'password123',
            'displayName' => 'Warehouse keeper',
            'email' => 'warehouse@example.com',
            'role' => 'manager',
            'isActive' => '1',
        ]);

        assertSame(302, $browser->status($response));

        $created = $services->users()->findByUsername('warehouse');

        assertSame(UserRole::Manager, $created?->role);
        assertSame('warehouse@example.com', $created?->email);
        assertStringContainsString(
            'User "warehouse" was created.',
            $browser->body($browser->get('/users')),
        );
    }

    public function invalidUserFormIsReportedOnTheForm(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/users/create', [
            'username' => 'ab',
            'password' => 'short',
            'displayName' => '',
            'email' => 'not-an-email',
            'role' => 'manager',
        ]);

        $body = $browser->body($response);

        assertSame(422, $browser->status($response));
        assertStringContainsString('The username must be at least 3 characters.', $body);
        assertStringContainsString('The password must be at least 8 characters.', $body);
        assertStringContainsString('Enter a valid email address.', $body);
    }

    public function duplicateUsernameIsRejected(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $services->userService()->create('staffer', 'password123', 'Staff', null, UserRole::Staff);

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/users/create', [
            'username' => 'staffer',
            'password' => 'password123',
            'displayName' => 'Duplicate',
            'email' => '',
            'role' => 'staff',
            'isActive' => '1',
        ]);

        assertSame(422, $browser->status($response));
        assertStringContainsString('Username "staffer" is already in use.', $browser->body($response));
    }

    public function adminUpdatesAndTogglesAUser(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $staff = $services->userService()->create('staffer', 'password123', 'Staff', null, UserRole::Staff);

        $browser = $I->loginAsAdmin();
        $browser->post('/users/' . $staff->id . '/edit', [
            'displayName' => 'Warehouse staff',
            'email' => '',
            'role' => 'manager',
            'isActive' => '1',
        ]);

        assertSame('Warehouse staff', $services->users()->findById($staff->id)?->displayName);
        assertSame(UserRole::Manager, $services->users()->findById($staff->id)?->role);

        $browser->post('/users/' . $staff->id . '/toggle');

        assertSame(false, $services->users()->findById($staff->id)?->isActive);
    }

    public function adminCannotDeactivateOwnAccount(FunctionalTester $I): void
    {
        $services = $I->services();
        $admin = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $browser = $I->loginAsAdmin();
        $browser->post('/users/' . $admin->id . '/toggle');

        assertSame(true, $services->users()->findById($admin->id)?->isActive);
        assertStringContainsString(
            'You cannot deactivate your own account.',
            $browser->body($browser->get('/users')),
        );
    }

    public function adminResetsAPassword(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $staff = $services->userService()->create('staffer', 'password123', 'Staff', null, UserRole::Staff);

        $browser = $I->loginAsAdmin();
        $browser->post('/users/' . $staff->id . '/password', ['password' => 'brand-new-password']);

        assertSame(
            $staff->id,
            $services->userService()->authenticate('staffer', 'brand-new-password')?->id,
        );
    }
}
