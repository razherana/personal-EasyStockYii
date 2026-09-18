<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class AuthCest
{
    public function guestIsRedirectedToLogin(FunctionalTester $I): void
    {
        $browser = $I->browser();

        $response = $browser->get('/products');

        assertSame(302, $browser->status($response));
        assertStringContainsString('/login', $browser->location($response));
    }

    public function loginPageRenders(FunctionalTester $I): void
    {
        $browser = $I->browser();

        $response = $browser->get('/login');

        assertSame(200, $browser->status($response));
        assertStringContainsString('Sign in', $browser->body($response));
    }

    public function wrongPasswordIsRejected(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $browser = $I->browser();
        $browser->get('/login');

        $response = $browser->post('/login', ['username' => 'admin', 'password' => 'nope']);

        assertSame(422, $browser->status($response));
        assertStringContainsString('Invalid username or password.', $browser->body($response));
    }

    public function loginAndLogout(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Site Administrator', null, UserRole::Admin);
        $browser = $I->browser();

        $login = $browser->login();

        assertSame(302, $browser->status($login));

        $dashboard = $browser->get('/');
        assertSame(200, $browser->status($dashboard));
        assertStringContainsString('Dashboard', $browser->body($dashboard));
        assertStringContainsString('Site Administrator', $browser->body($dashboard));

        $logout = $browser->post('/logout');
        assertSame(302, $browser->status($logout));

        $afterLogout = $browser->get('/');
        assertSame(302, $browser->status($afterLogout));
    }

    public function inactiveUserCannotSignIn(FunctionalTester $I): void
    {
        $services = $I->services();
        $admin = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $user = $services->userService()->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);
        $services->userService()->setActive($user->id, false, $admin->id);

        $browser = $I->browser();
        $browser->get('/login');
        $response = $browser->post('/login', ['username' => 'staffer', 'password' => 'password123']);

        assertSame(422, $browser->status($response));
        assertStringContainsString('Invalid username or password.', $browser->body($response));
    }

    public function postWithoutCsrfTokenIsRejected(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $browser = $I->browser();
        $browser->login();

        $response = $browser->request('POST', '/products/create', [
            'sku' => 'CHAIR',
            'name' => 'Chair',
        ]);

        // The CSRF middleware answers with a 422 for a request without a valid token.
        assertSame(422, $browser->status($response));
    }
}
