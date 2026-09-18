<?php

declare(strict_types=1);

namespace App\Tests\Console;

use App\Tests\Support\ConsoleTester;
use App\User\UserRole;

use function assert;
use function PHPUnit\Framework\assertSame;

final readonly class UserCreateCest
{
    public function createsAUser(ConsoleTester $I): void
    {
        $I->runApp(
            'user:create --username=warehouse --password=password123 --name="Warehouse keeper" '
            . '--email=warehouse@example.com --role=staff',
        );

        $I->canSeeResultCodeIs(0);
        $I->seeInShellOutput('User "warehouse" created with role "staff".');

        $user = $I->services()->users()->findByUsername('warehouse');

        assert($user !== null);
        assertSame(UserRole::Staff, $user->role);
        assertSame('Warehouse keeper', $user->displayName);
        assertSame('warehouse@example.com', $user->email);
    }

    public function defaultsToAdministratorRole(ConsoleTester $I): void
    {
        $I->runApp('user:create --username=admin --password=password123');

        $I->canSeeResultCodeIs(0);

        $user = $I->services()->users()->findByUsername('admin');

        assert($user !== null);
        assertSame(UserRole::Admin, $user->role);
        assertSame('admin', $user->displayName);
    }

    public function rejectsADuplicateUsername(ConsoleTester $I): void
    {
        $I->services()->userService()->create('warehouse', 'password123', 'Warehouse', null, UserRole::Staff);

        $I->runApp('user:create --username=warehouse --password=password123', expectSuccess: false);

        $I->seeResultCodeIs(64);
        $I->seeInShellOutput('Username "warehouse" is already in use.');
    }

    public function rejectsAnUnknownRole(ConsoleTester $I): void
    {
        $I->runApp('user:create --username=warehouse --password=password123 --role=owner', expectSuccess: false);

        $I->seeResultCodeIs(64);
        $I->seeInShellOutput('Unknown role "owner"');
    }

    public function requiresUsernameAndPassword(ConsoleTester $I): void
    {
        $I->runApp('user:create', expectSuccess: false);

        $I->seeResultCodeIs(64);
        $I->seeInShellOutput('Options --username and --password are required.');
    }
}
