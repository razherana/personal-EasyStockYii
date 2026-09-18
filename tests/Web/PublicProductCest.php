<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Products\ProductData;
use App\Tests\Support\WebTester;
use App\User\UserRole;

final class PublicProductCest
{
    public function qrCodePageIsPublic(WebTester $I): void
    {
        $services = $I->services();
        $author = $services->userService()->create('admin', 'password123', 'Site Administrator', null, UserRole::Admin);

        $product = $services->productService()->create(new ProductData(
            sku: 'SHELF-1',
            name: 'Shelf 1',
            unit: 'piece',
            description: 'Booked for the public page test.',
            lowStockThreshold: 2,
        ));

        $variant = $services->variants()->findByProductId($product->id, true)[0];

        $services->stockService()->record(
            type: \App\Stock\MovementType::In,
            variantId: $variant->id,
            quantity: 7,
            reference: 'Opening',
            note: null,
            unitCost: null,
            userId: $author->id,
        );

        $I->amOnPage('/p/' . $product->qrToken);

        $I->see('Shelf 1');
        $I->see('SHELF-1');
        $I->see('7');
        $I->see('Available');
    }

    public function qrCodeSvgIsServed(WebTester $I): void
    {
        $services = $I->services();
        $product = $services->productService()->create(new ProductData(sku: 'SHELF-2', name: 'Shelf 2'));

        $I->amOnPage('/p/' . $product->qrToken . '/qr.svg');

        $I->canSeeResponseCodeIs(200);
        $I->seeInSource('<svg');
    }

    public function unknownTokenShowsNotFound(WebTester $I): void
    {
        $I->amOnPage('/p/this-token-does-not-exist');

        $I->canSeeResponseCodeIs(404);
    }
}
