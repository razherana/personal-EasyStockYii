<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Stock\MovementType;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function str_contains;

final class PublicProductCest
{
    /**
     * @return array{0: \App\Products\Product, 1: \App\Tests\Support\Services}
     */
    private function fixture(FunctionalTester $I): array
    {
        $services = $I->services();
        $admin = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'TABLE-5FT',
            name: 'Table 5ft black',
        ));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;
        $services->stockService()->record(MovementType::In, $variantId, 3, 'Opening', null, null, $admin->id);

        return [$product, $services];
    }

    public function productPageIsPublic(FunctionalTester $I): void
    {
        [$product] = $this->fixture($I);
        $browser = $I->browser();

        $response = $browser->get('/p/' . $product->qrToken);
        $body = $browser->body($response);

        assertSame(200, $browser->status($response));
        assertStringContainsString('Table 5ft black', $body);
        assertStringContainsString('Product summary', $body);
        assertStringContainsString('TABLE-5FT', $body);
        assertStringContainsString('Available', $body);
    }

    public function qrCodeIsServedAsSvg(FunctionalTester $I): void
    {
        [$product] = $this->fixture($I);
        $browser = $I->browser();

        $response = $browser->get('/p/' . $product->qrToken . '/qr.svg');

        assertSame(200, $browser->status($response));
        assertStringContainsString('image/svg+xml', $browser->header($response, 'Content-Type'));
        assertStringContainsString('<svg', $browser->body($response));
    }

    public function unknownTokenReturnsNotFound(FunctionalTester $I): void
    {
        $browser = $I->browser();

        assertSame(404, $browser->status($browser->get('/p/does-not-exist')));
        assertSame(404, $browser->status($browser->get('/p/does-not-exist/qr.svg')));
    }

    public function loginIsNotRequiredAndTheAdminLayoutIsNotUsed(FunctionalTester $I): void
    {
        [$product] = $this->fixture($I);

        // A fresh client without any session.
        $browser = $I->browser();
        $body = $browser->body($browser->get('/p/' . $product->qrToken));

        assertStringContainsString('public-page', $body);
        assertSame(false, str_contains($body, 'sidebar'));
    }
}
