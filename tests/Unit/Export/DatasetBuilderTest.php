<?php

declare(strict_types=1);

namespace App\Tests\Unit\Export;

use App\Export\DatasetBuilder;
use App\Export\ExportDatasetName;
use App\Export\ExportFormat;
use App\Export\ExportService;
use App\Products\ProductData;
use App\Stock\MovementType;
use App\Tests\Support\DatabaseTestCase;
use App\User\UserRole;
use Yiisoft\Aliases\Aliases;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function unlink;

final class DatasetBuilderTest extends DatabaseTestCase
{
    public function testStockDatasetContainsOptionLabelsAndLevels(): void
    {
        $services = $this->services();
        $options = $services->options();
        $typeId = $options->createType('Size');
        $size = $options->createValue($typeId, 'XL');
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'TSHIRT',
            name: 'T-Shirt',
            lowStockThreshold: 5,
            optionValueIds: [$size],
        ));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;
        $services->stockService()->record(MovementType::In, $variantId, 12, 'BL-1', null, null, $user->id);

        $dataset = (new DatasetBuilder($services->products(), $services->stock(), $options))
            ->build(ExportDatasetName::Stock);

        assertSame(
            ['product_sku', 'product', 'variant_sku', 'options', 'on_hand', 'unit', 'low_stock_threshold',
                'low_stock', 'last_movement'],
            $dataset->headers,
        );
        assertCount(1, $dataset->rows);
        assertSame('Size: XL', $dataset->rows[0]['options'] ?? null);
        assertSame(12, $dataset->rows[0]['on_hand'] ?? null);
        assertSame(0, $dataset->rows[0]['low_stock'] ?? null);
    }

    public function testImportTemplateHasExampleRow(): void
    {
        $dataset = (new DatasetBuilder(
            $this->services()->products(),
            $this->services()->stock(),
            $this->services()->options(),
        ))->build(ExportDatasetName::ImportTemplate);

        assertCount(1, $dataset->rows);
        assertSame('TSHIRT-XL-RED', $dataset->rows[0]['sku'] ?? null);
    }

    public function testExportServiceWritesEveryFormat(): void
    {
        $services = $this->services();
        $aliases = new Aliases(['@runtime' => dirname(__DIR__, 3) . '/runtime']);
        $exportService = new ExportService(
            new DatasetBuilder($services->products(), $services->stock(), $services->options()),
            new \App\Export\CsvExporter(),
            new \App\Export\XlsxExporter(),
            new \App\Export\PdfExporter(),
            $aliases,
        );

        $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));
        $dataset = $exportService->dataset(ExportDatasetName::Products);

        foreach (ExportFormat::cases() as $format) {
            $filePath = $exportService->exportToFile($dataset, $format);

            assertFileExists($filePath);
            assertStringContainsString('.' . $format->extension(), $filePath);

            unlink($filePath);
        }
    }
}
