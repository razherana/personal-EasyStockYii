<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

final class ProductCest
{
    public function listShowsProducts(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $I->services()->productService()->create(new ProductData(sku: 'CHAIR', name: 'Wooden chair'));

        $browser = $I->loginAsAdmin();
        $response = $browser->get('/products');

        assertSame(200, $browser->status($response));
        assertStringContainsString('Wooden chair', $browser->body($response));
        assertStringContainsString('CHAIR', $browser->body($response));
    }

    public function createProductWithOptionsGeneratesVariants(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $optionTypeId = $services->options()->createType('Color');
        $black = $services->options()->createValue($optionTypeId, 'Black');

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/products/create', [
            'sku' => 'TABLE-5FT',
            'name' => 'Table 5ft black',
            'unit' => 'piece',
            'lowStockThreshold' => '3',
            'isActive' => '1',
            'optionValueIds' => [(string) $black],
        ]);

        assertSame(302, $browser->status($response));

        $product = $services->products()->findBySku('TABLE-5FT');
        assertTrue($product !== null);

        $variants = $services->variants()->findByProductId($product->id);
        assertCount(1, $variants);
        assertSame('TABLE-5FT-BLACK', $variants[0]->sku);
        assertSame(['Color: Black'], $variants[0]->optionLabels);

        $detail = $browser->get('/products/' . $product->id);
        assertStringContainsString('TABLE-5FT-BLACK', $browser->body($detail));
        assertStringContainsString('Color: Black', $browser->body($detail));
    }

    public function duplicateSkuShowsAnErrorOnTheForm(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/products/create', [
            'sku' => 'CHAIR',
            'name' => 'Another chair',
            'isActive' => '1',
        ]);

        assertSame(422, $browser->status($response));
        assertStringContainsString('SKU "CHAIR" is already used', $browser->body($response));
    }

    public function editUpdatesTheProduct(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/products/' . $product->id . '/edit', [
            'sku' => 'CHAIR',
            'name' => 'Chair deluxe',
            'unit' => 'piece',
            'lowStockThreshold' => '',
            'isActive' => '1',
        ]);

        assertSame(302, $browser->status($response));
        assertSame('Chair deluxe', $services->products()->findById($product->id)?->name);
    }

    public function deactivateHidesTheProductFromTheList(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));

        $browser = $I->loginAsAdmin();
        $response = $browser->post('/products/' . $product->id . '/delete');

        assertSame(302, $browser->status($response));
        assertSame(false, $services->products()->findById($product->id)?->isActive);

        $list = $browser->get('/products');
        assertStringContainsString('No products found.', $browser->body($list));
    }

    public function unknownProductReturnsNotFound(FunctionalTester $I): void
    {
        $I->services()->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/products/999');

        assertSame(404, $browser->status($response));
        assertStringContainsString('Product not found', $browser->body($response));
    }

    public function staffCannotOpenTheCreateForm(FunctionalTester $I): void
    {
        $services = $I->services();
        $services->userService()->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);

        $browser = $I->browser();
        $browser->login('staffer', 'password123');

        $response = $browser->get('/products/create');

        assertSame(403, $browser->status($response));
        assertStringContainsString('Access denied', $browser->body($response));
    }
}
