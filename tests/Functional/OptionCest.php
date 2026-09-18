<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function count;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class OptionCest
{
    public function createTypeAndValue(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $browser = $I->loginAsAdmin();

        $browser->post('/options', ['name' => 'Size']);
        $types = $I->services()->options()->findTypes();

        assertSame(1, count($types));

        $browser->post('/options/' . $types[0]->id . '/values', ['value' => 'XL']);
        $page = $browser->body($browser->get('/options'));

        assertStringContainsString('Size', $page);
        assertStringContainsString('XL', $page);
    }

    public function duplicateTypeShowsAnError(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $browser = $I->loginAsAdmin();
        $browser->post('/options', ['name' => 'Size']);

        $browser->post('/options', ['name' => 'Size']);

        assertStringContainsString(
            'Option type "Size" already exists.',
            $browser->body($browser->get('/options')),
        );
    }

    public function emptyNameIsReported(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $browser = $I->loginAsAdmin();

        $browser->post('/options', ['name' => '  ']);

        assertStringContainsString(
            'Enter an option type name.',
            $browser->body($browser->get('/options')),
        );
    }

    public function valuesUsedByVariantsCannotBeDeleted(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $typeId = $services->options()->createType('Color');
        $valueId = $services->options()->createValue($typeId, 'Black');
        $services->productService()->create(new ProductData(
            sku: 'TABLE',
            name: 'Table',
            optionValueIds: [$valueId],
        ));

        $browser = $I->loginAsAdmin();
        $browser->post('/options/' . $typeId . '/delete');
        $browser->post('/options/' . $typeId . '/values/' . $valueId . '/delete');

        $page = $browser->body($browser->get('/options'));

        assertStringContainsString('is used by product variants and cannot be deleted', $page);
        assertSame(1, count($services->options()->findValues()));
    }

    public function unusedValuesCanBeDeleted(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $typeId = $services->options()->createType('Logo');
        $valueId = $services->options()->createValue($typeId, 'With logo');

        $browser = $I->loginAsAdmin();
        $browser->post('/options/' . $typeId . '/values/' . $valueId . '/delete');

        assertSame(0, count($services->options()->findValues($typeId)));
    }

    public function staffCannotManageOptionTypes(FunctionalTester $I): void
    {
        $I->services()->userService()->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);
        $browser = $I->browser();
        $browser->login('staffer', 'password123');

        assertSame(403, $browser->status($browser->get('/options')));
    }
}
