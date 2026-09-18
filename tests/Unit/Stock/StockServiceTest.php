<?php

declare(strict_types=1);

namespace App\Tests\Unit\Stock;

use App\Products\ProductData;
use App\Stock\MovementType;
use App\Stock\StockException;
use App\Tests\Support\DatabaseTestCase;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class StockServiceTest extends DatabaseTestCase
{
    private function fixture(): array
    {
        $services = $this->services();
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, \App\User\UserRole::Admin);
        $product = $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));
        $variant = $services->variants()->findByProductId($product->id)[0];

        return [$services, $variant->id, $user->id];
    }

    public function testStockInIncreasesTheLevel(): void
    {
        [$services, $variantId, $userId] = $this->fixture();

        $movement = $services->stockService()->record(
            MovementType::In,
            $variantId,
            10,
            'BL-1',
            'Opening stock',
            12.5,
            $userId,
        );

        assertSame(10, $movement->quantityChange);
        assertSame(10, $services->stock()->levelForVariant($variantId));
        assertTrue($movement->createdAt->getTimestamp() > 0);
    }

    public function testStockOutDecreasesTheLevel(): void
    {
        [$services, $variantId, $userId] = $this->fixture();
        $service = $services->stockService();
        $service->record(MovementType::In, $variantId, 10, null, null, null, $userId);

        $service->record(MovementType::Out, $variantId, 4, 'Invoice 12', null, null, $userId);

        assertSame(6, $services->stock()->levelForVariant($variantId));
    }

    public function testStockOutCannotGoBelowZero(): void
    {
        [$services, $variantId, $userId] = $this->fixture();
        $service = $services->stockService();
        $service->record(MovementType::In, $variantId, 2, null, null, null, $userId);

        $this->expectException(StockException::class);
        $this->expectExceptionMessage('Not enough stock: only 2 available.');

        $service->record(MovementType::Out, $variantId, 3, null, null, null, $userId);
    }

    public function testAdjustmentAcceptsSignedChanges(): void
    {
        [$services, $variantId, $userId] = $this->fixture();
        $service = $services->stockService();
        $service->record(MovementType::In, $variantId, 10, null, null, null, $userId);

        $service->record(MovementType::Adjustment, $variantId, -3, null, 'Damaged', null, $userId);

        assertSame(7, $services->stock()->levelForVariant($variantId));
    }

    public function testZeroQuantityIsRejected(): void
    {
        [$services, $variantId, $userId] = $this->fixture();

        $this->expectException(StockException::class);
        $this->expectExceptionMessage('Enter a quantity change that is not zero.');

        $services->stockService()->record(MovementType::Adjustment, $variantId, 0, null, null, null, $userId);
    }

    public function testNegativeQuantityIsRejectedForInAndOut(): void
    {
        [$services, $variantId, $userId] = $this->fixture();

        $this->expectException(StockException::class);
        $this->expectExceptionMessage('Enter a quantity greater than zero for a "in" movement.');

        $services->stockService()->record(MovementType::In, $variantId, -1, null, null, null, $userId);
    }

    public function testHistoryIsNewestFirstAndCarriesTheAuthor(): void
    {
        [$services, $variantId, $userId] = $this->fixture();
        $service = $services->stockService();
        $service->record(MovementType::In, $variantId, 5, 'BL-1', null, null, $userId);
        $service->record(MovementType::Out, $variantId, 1, 'INV-2', null, null, $userId);

        $movements = $services->stock()->movementsForVariant($variantId);

        assertCount(2, $movements);
        assertSame(MovementType::Out, $movements[0]->type);
        assertSame('Admin', $movements[0]->createdByName);
    }

    public function testLevelsReportLowStockAndProducts(): void
    {
        [$services, $variantId, $userId] = $this->fixture();
        $services->stockService()->record(MovementType::In, $variantId, 3, null, null, null, $userId);

        $level = $services->stock()->levelOfVariant($variantId);

        assertTrue($level !== null);
        assertSame(3, $level->onHand);
        assertSame('CHAIR', $level->productSku);
        assertSame('Chair', $level->productName);
        assertSame(false, $level->isLowStock());
        assertSame(3, $services->stock()->totalOnHand());
        assertSame(1, $services->stock()->countLevels());
        assertSame(1, count($services->stock()->levelsForProduct($level->productId)));
    }

    public function testLowStockFilterAndCount(): void
    {
        $services = $this->services();
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, \App\User\UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            lowStockThreshold: 5,
        ));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;
        $services->stockService()->record(MovementType::In, $variantId, 2, null, null, null, $user->id);

        assertSame(1, $services->stock()->countLowStockLevels());
        assertSame(1, count($services->stock()->levels('', true)));
        assertSame(0, count($services->stock()->levels('nothing-matches')));
        assertSame(1, $services->stock()->countLevels('', false));
    }
}
