<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class StockCest
{
    /**
     * @return array{0: int, 1: \App\Tests\Support\Services}
     */
    private function fixture(FunctionalTester $I): array
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            lowStockThreshold: 3,
        ));

        return [$services->variants()->findByProductId($product->id)[0]->id, $services];
    }

    public function stockListShowsVariants(FunctionalTester $I): void
    {
        $this->fixture($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/stock');

        assertSame(200, $browser->status($response));
        assertStringContainsString('CHAIR', $browser->body($response));
        assertStringContainsString('Out of stock', $browser->body($response));
    }

    public function recordingStockIncreasesTheLevel(FunctionalTester $I): void
    {
        [$variantId, $services] = $this->fixture($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'in',
            'quantity' => '7',
            'reference' => 'BL-77',
            'note' => 'Delivery',
        ]);

        assertSame(302, $browser->status($response));
        assertSame(7, $services->stock()->levelForVariant($variantId));

        $page = $browser->get('/stock/variants/' . $variantId);
        $body = $browser->body($page);

        assertStringContainsString('Stock in', $body);
        assertStringContainsString('BL-77', $body);
        assertStringContainsString('Delivery', $body);
    }

    public function stockOutBeyondTheLevelIsRefused(FunctionalTester $I): void
    {
        [$variantId, $services] = $this->fixture($I);
        $browser = $I->loginAsAdmin();
        $browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'in',
            'quantity' => '2',
        ]);

        $browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'out',
            'quantity' => '5',
        ]);

        assertSame(2, $services->stock()->levelForVariant($variantId));
        assertStringContainsString(
            'Not enough stock: only 2 available.',
            $browser->body($browser->get('/stock/variants/' . $variantId)),
        );
    }

    public function adjustmentAcceptsNegativeChanges(FunctionalTester $I): void
    {
        [$variantId, $services] = $this->fixture($I);
        $browser = $I->loginAsAdmin();
        $browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'in',
            'quantity' => '10',
        ]);

        $browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'adjustment',
            'quantity' => '-4',
            'note' => 'Damaged',
        ]);

        assertSame(6, $services->stock()->levelForVariant($variantId));
    }

    public function invalidQuantityIsReported(FunctionalTester $I): void
    {
        [$variantId, $services] = $this->fixture($I);
        $browser = $I->loginAsAdmin();

        $browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'in',
            'quantity' => 'abc',
        ]);

        assertSame(0, $services->stock()->levelForVariant($variantId));
        assertStringContainsString(
            'Enter the quantity as a whole number.',
            $browser->body($browser->get('/stock/variants/' . $variantId)),
        );
    }

    public function stockIsVisibleForStaffButMovementsRequireThePermission(FunctionalTester $I): void
    {
        [$variantId] = $this->fixture($I);
        $I->services()->userService()->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);

        $browser = $I->browser();
        $browser->login('staffer', 'password123');

        assertSame(200, $browser->status($browser->get('/stock')));
        assertSame(302, $browser->status($browser->post('/stock/movements', [
            'variantId' => (string) $variantId,
            'type' => 'in',
            'quantity' => '1',
        ])));
    }
}
