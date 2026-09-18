<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Tests\Support\WebTester;
use App\User\UserRole;

final class LoginCest
{
    public function guestsAreSentToTheLoginPage(WebTester $I): void
    {
        $I->amOnPage('/');

        $I->seeInCurrentUrl('/login');
        $I->see('Sign in');
    }

    public function wrongPasswordIsRejected(WebTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Site Administrator', null, UserRole::Admin);

        $I->amOnPage('/login');
        $I->fillField('username', 'admin');
        $I->fillField('password', 'wrong-password');
        $I->click('Sign in');

        $I->see('Invalid username or password.');
        $I->seeInCurrentUrl('/login');
    }

    public function signInShowsTheDashboardAndSignOutLeavesIt(WebTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Site Administrator', null, UserRole::Admin);

        $I->amOnPage('/login');
        $I->fillField('username', 'admin');
        $I->fillField('password', 'password123');
        $I->click('Sign in');

        $I->see('Dashboard');
        $I->see('Site Administrator');
        $I->see('Products');

        $I->click('Log out');

        $I->seeInCurrentUrl('/login');

        $I->amOnPage('/');
        $I->seeInCurrentUrl('/login');
    }
}
