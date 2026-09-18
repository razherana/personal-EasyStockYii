<?php

declare(strict_types=1);

namespace App\Tests\Unit\Import;

use App\Import\ImportResult;
use App\Import\ProductImporter;
use App\Products\ProductData;
use App\Tests\Support\DatabaseTestCase;
use App\User\UserRole;

use function implode;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

final class ProductImporterTest extends DatabaseTestCase
{
    private ProductImporter $importer;
    private int $userId;

    protected function _before(): void
    {
        parent::_before();

        $services = $this->services();

        $this->importer = new ProductImporter(
            $services->options(),
            $services->products(),
            $services->variants(),
            $services->productService(),
            $services->stockService(),
        );

        $this->userId = $services->userService()
            ->create('admin', 'password123', 'Admin', null, UserRole::Admin)
            ->id;
    }

    public function testImportCreatesProductsVariantsAndOpeningStock(): void
    {
        $result = $this->importer->import([
            1 => [
                'sku' => 'TSHIRT',
                'name' => 'T-Shirt',
                'unit' => 'piece',
                'low_stock_threshold' => '5',
                'active' => '1',
                'options' => 'Size=XL; Color=Red',
                'quantity' => '10',
            ],
        ], $this->userId);

        $services = $this->services();
        $product = $services->products()->findBySku('TSHIRT');

        assertSame(1, $result->created);
        assertSame(0, $result->updated);
        assertSame(0, $result->skipped);
        assertCount(0, $result->errors);
        assertTrue($product !== null);
        assertSame(5, $product?->lowStockThreshold);
        assertSame('Size: XL; Color: Red', $this->optionsOf('TSHIRT'));
        assertSame(10, $services->stock()->totalOnHand());
    }

    public function testImportUpdatesAnExistingProduct(): void
    {
        $this->services()->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Old chair name',
        ));

        $result = $this->importer->import([
            1 => ['sku' => 'CHAIR', 'name' => 'Chair', 'unit' => '', 'low_stock_threshold' => '', 'active' => '1',
                'options' => '', 'quantity' => ''],
        ], $this->userId);

        assertSame(0, $result->created);
        assertSame(1, $result->updated);
        assertSame('Chair', $this->services()->products()->findBySku('CHAIR')?->name);
        assertSame('piece', $this->services()->products()->findBySku('CHAIR')?->unit);
    }

    public function testImportReportsMissingSkuAndName(): void
    {
        $result = $this->importer->import([
            1 => ['sku' => '', 'name' => 'No SKU'],
            2 => ['sku' => 'CHAIR', 'name' => ''],
        ], $this->userId);

        assertSame(2, $result->rows);
        assertSame(0, $result->created);
        assertCount(2, $result->errors);
        assertStringContainsString('Line 1: the "sku" column is required.', $result->errors[0]);
        assertStringContainsString('Line 2 (CHAIR): the "name" column is required.', $result->errors[1]);
        assertTrue(str_contains($result->summary(), '2 error(s)'));
    }

    public function testImportReportsInvalidOptions(): void
    {
        $result = $this->importer->import([
            1 => ['sku' => 'CHAIR', 'name' => 'Chair', 'options' => 'SizeXL'],
        ], $this->userId);

        assertCount(1, $result->errors);
        assertStringContainsString('is not a valid option, expected "Type=Value"', $result->errors[0]);
    }

    public function testQuantityIsSkippedForProductsWithSeveralVariants(): void
    {
        $result = $this->importer->import([
            1 => [
                'sku' => 'TSHIRT',
                'name' => 'T-Shirt',
                'options' => 'Size=XL; Size=L',
                'quantity' => '5',
            ],
        ], $this->userId);

        assertSame(1, $result->created);
        assertSame(1, $result->skipped);
        assertStringContainsString('the quantity was not applied', $result->errors[0]);
        assertSame(0, $this->services()->stock()->totalOnHand());
    }

    public function testImportMayCreateInactiveProducts(): void
    {
        $this->importer->import([
            1 => ['sku' => 'CHAIR', 'name' => 'Chair', 'active' => '0'],
        ], $this->userId);

        assertSame(false, $this->services()->products()->findBySku('CHAIR')?->isActive);
    }

    public function testEmptyResultSummary(): void
    {
        $result = ImportResult::empty();

        assertSame('0 row(s) read: 0 created, 0 updated, 0 skipped, 0 error(s).', $result->summary());
        assertSame(false, $result->hasErrors());
    }

    private function optionsOf(string $sku): string
    {
        $services = $this->services();
        $product = $services->products()->findBySku($sku);
        $variant = $product === null ? null : $services->variants()->findByProductId($product->id)[0] ?? null;

        if ($variant === null) {
            return '';
        }

        return implode('; ', $variant->optionLabels);
    }
}
