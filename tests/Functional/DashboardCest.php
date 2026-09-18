<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Stock\MovementType;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class DashboardCest
{
    public function dashboardShowsStockSummary(FunctionalTester $I): void
    {
        $services = $I->services();
        $admin = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            lowStockThreshold: 5,
        ));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;
        $services->stockService()->record(MovementType::In, $variantId, 12, 'Opening', null, null, $admin->id);

        $browser = $I->loginAsAdmin();
        $response = $browser->get('/');
        $body = $browser->body($response);

        assertSame(200, $browser->status($response));
        assertStringContainsString('Active products', $body);
        assertStringContainsString('Units in stock', $body);
        assertStringContainsString('12', $body);
        assertStringContainsString('Chair', $body);
        assertStringContainsString('Latest movements', $body);
    }

    public function lowStockIsHighlighted(FunctionalTester $I): void
    {
        $services = $I->services();
        $admin = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            lowStockThreshold: 5,
        ));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;
        $services->stockService()->record(MovementType::In, $variantId, 2, null, null, null, $admin->id);

        $browser = $I->loginAsAdmin();
        $body = $browser->body($browser->get('/'));

        assertStringContainsString('Low stock variants', $body);
        assertStringContainsString('Chair', $body);
        assertStringContainsString('Low stock', $browser->body($browser->get('/products')));
    }
}
