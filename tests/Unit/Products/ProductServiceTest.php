<?php

declare(strict_types=1);

namespace App\Tests\Unit\Products;

use App\Products\ProductData;
use App\Products\ProductException;
use App\Tests\Support\DatabaseTestCase;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class ProductServiceTest extends DatabaseTestCase
{
    public function testCreateProductWithoutOptionsCreatesOneDefaultVariant(): void
    {
        $services = $this->services();
        $product = $services->productService()->create(new ProductData(
            sku: 'SCREWS-500',
            name: 'Screws box 500',
            lowStockThreshold: 5,
        ));

        $variants = $services->variants()->findByProductId($product->id);

        assertCount(1, $variants);
        assertTrue($variants[0]->isDefault);
        assertSame('SCREWS-500', $variants[0]->sku);
        assertSame(5, $product->lowStockThreshold);
        assertNotSame('', $product->qrToken);
    }

    public function testCreateProductWithOptionsGeneratesVariants(): void
    {
        $services = $this->services();
        $options = $services->options();
        $typeId = $options->createType('Size');
        $xl = $options->createValue($typeId, 'XL');
        $l = $options->createValue($typeId, 'L');

        $product = $services->productService()->create(new ProductData(
            sku: 'TSHIRT',
            name: 'T-Shirt',
            optionValueIds: [$xl, $l],
        ));

        $variants = $services->variants()->findByProductId($product->id);

        assertCount(2, $variants);
        assertSame(['Size: L', 'Size: XL'], [
            $variants[0]->optionLabels[0] ?? '',
            $variants[1]->optionLabels[0] ?? '',
        ]);
    }

    public function testUpdateRegeneratesVariantsAndKeepsExistingOnes(): void
    {
        $services = $this->services();
        $options = $services->options();
        $typeId = $options->createType('Color');
        $red = $options->createValue($typeId, 'Red');
        $black = $options->createValue($typeId, 'Black');

        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            optionValueIds: [$red],
        ));

        $before = $services->variants()->findByProductId($product->id);

        $services->productService()->update($product->id, new ProductData(
            sku: 'CHAIR',
            name: 'Chair deluxe',
            optionValueIds: [$red, $black],
        ));

        $after = $services->variants()->findByProductId($product->id);
        $idsBySku = [];

        foreach ($after as $variant) {
            $idsBySku[$variant->sku] = $variant->id;
        }

        assertCount(2, $after);
        assertSame($before[0]->id, $idsBySku['CHAIR-RED']);
        assertSame('Chair deluxe', $services->products()->findById($product->id)?->name);
    }

    public function testUpdateDeactivatesVariantsThatAreNoLongerSelected(): void
    {
        $services = $this->services();
        $options = $services->options();
        $typeId = $options->createType('Color');
        $red = $options->createValue($typeId, 'Red');
        $black = $options->createValue($typeId, 'Black');

        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            optionValueIds: [$red, $black],
        ));

        $services->productService()->update($product->id, new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            optionValueIds: [$red],
        ));

        $variants = $services->variants()->findByProductId($product->id);
        $active = [];

        foreach ($variants as $variant) {
            if ($variant->isActive) {
                $active[] = $variant->sku;
            }
        }

        assertSame(['CHAIR-RED'], $active);
    }

    public function testDuplicateSkuIsRejected(): void
    {
        $service = $this->services()->productService();
        $service->create(new ProductData(sku: 'CHAIR', name: 'Chair'));

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('SKU "CHAIR" is already used by another product.');

        $service->create(new ProductData(sku: 'CHAIR', name: 'Other chair'));
    }

    public function testDeactivateHidesTheProductFromTheList(): void
    {
        $services = $this->services();
        $product = $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));

        $services->productService()->deactivate($product->id);

        assertCount(0, $services->products()->search());
        assertFalse($services->products()->findById($product->id)?->isActive ?? true);
        assertSame(1, count($services->products()->search('', true)));
    }

    public function testQrTokenIsUniquePerProduct(): void
    {
        $services = $this->services();
        $first = $services->productService()->create(new ProductData(sku: 'A', name: 'A'));
        $second = $services->productService()->create(new ProductData(sku: 'B', name: 'B'));

        assertNotSame($first->qrToken, $second->qrToken);
        assertSame($first->id, $services->products()->findByQrToken($first->qrToken)?->id);
    }
}
