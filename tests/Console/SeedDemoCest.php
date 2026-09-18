<?php

declare(strict_types=1);

namespace App\Tests\Console;

use App\Tests\Support\ConsoleTester;
use App\User\UserRole;

use function assert;
use function count;
use function PHPUnit\Framework\assertSame;

final readonly class SeedDemoCest
{
    public function seedsDemoData(ConsoleTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $I->runApp('seed:demo');

        $I->canSeeResultCodeIs(0);
        $I->seeInShellOutput('Demo data ready: 3 option types, 3 products, 3 variants.');

        $services = $I->services();

        assertSame(3, $services->products()->countAll());
        assertSame(3, $services->variants()->countAll());

        $shirt = $services->products()->findBySku('TSHIRT');

        assert($shirt !== null);
        assertSame('T-Shirt XL red with logo', $shirt->name);
    }

    public function runningItTwiceKeepsTheDataStable(ConsoleTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $I->runApp('seed:demo');
        $I->runApp('seed:demo');

        $I->canSeeResultCodeIs(0);

        $services = $I->services();

        assertSame(3, $services->products()->countAll());
        assertSame(3, $services->variants()->countAll());

        $shirt = $services->products()->findBySku('TSHIRT');

        assert($shirt !== null);

        $variants = $services->variants()->findByProductId($shirt->id, true);

        assertSame(1, count($variants));
        assertSame(24, $services->stock()->levelForVariant($variants[0]->id));
    }

    public function requiresAUserToAttributeStockTo(ConsoleTester $I): void
    {
        $I->runApp('seed:demo', expectSuccess: false);

        $I->seeResultCodeIs(64);
        $I->seeInShellOutput('Create a user first: ./yii user:create');
    }
}
