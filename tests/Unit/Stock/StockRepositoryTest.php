<?php

declare(strict_types=1);

namespace App\Tests\Unit\Stock;

use App\Products\ProductData;
use App\Stock\MovementType;
use App\Tests\Support\DatabaseTestCase;
use App\User\UserRole;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;

/**
 * Aggregates powering the analytics page.
 */
final class StockRepositoryTest extends DatabaseTestCase
{
    public function testDailyMovementTotalsAreGroupedPerDayAndType(): void
    {
        $services = $this->services();
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;

        $stock = $services->stockService();
        $stock->record(MovementType::In, $variantId, 10, null, null, null, $user->id);
        $stock->record(MovementType::Out, $variantId, 4, null, null, null, $user->id);
        $stock->record(MovementType::Adjustment, $variantId, -1, null, null, null, $user->id);

        $totals = $services->stock()->dailyMovementTotals(7);

        assertCount(1, $totals);
        assertSame(10, $totals[0]['incoming']);
        assertSame(4, $totals[0]['outgoing']);
        assertSame(-1, $totals[0]['adjustment']);
    }

    public function testLevelStatusCountsSplitOutLowAndHealthy(): void
    {
        $services = $this->services();
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $healthy = $services->productService()->create(new ProductData(
            sku: 'HEALTHY',
            name: 'Healthy',
            lowStockThreshold: 3,
        ));
        $low = $services->productService()->create(new ProductData(
            sku: 'LOW',
            name: 'Low',
            lowStockThreshold: 5,
        ));
        $services->productService()->create(new ProductData(
            sku: 'EMPTY',
            name: 'Empty',
            lowStockThreshold: 3,
        ));

        $stock = $services->stockService();
        $stock->record(
            MovementType::In,
            $services->variants()->findByProductId($healthy->id)[0]->id,
            10,
            null,
            null,
            null,
            $user->id,
        );
        $stock->record(
            MovementType::In,
            $services->variants()->findByProductId($low->id)[0]->id,
            2,
            null,
            null,
            null,
            $user->id,
        );

        assertSame(
            ['out' => 1, 'low' => 1, 'healthy' => 1],
            $services->stock()->levelStatusCounts(),
        );
    }

    public function testTopProductsByOnHandAreOrderedDescending(): void
    {
        $services = $this->services();
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);

        $small = $services->productService()->create(new ProductData(sku: 'SMALL', name: 'Small'));
        $large = $services->productService()->create(new ProductData(sku: 'LARGE', name: 'Large'));

        $stock = $services->stockService();
        $stock->record(
            MovementType::In,
            $services->variants()->findByProductId($small->id)[0]->id,
            3,
            null,
            null,
            null,
            $user->id,
        );
        $stock->record(
            MovementType::In,
            $services->variants()->findByProductId($large->id)[0]->id,
            25,
            null,
            null,
            null,
            $user->id,
        );

        $top = $services->stock()->topProductsByOnHand(5);

        assertCount(2, $top);
        assertSame('Large', $top[0]['name']);
        assertSame(25, $top[0]['onHand']);
        assertSame('Small', $top[1]['name']);
        assertSame(3, $top[1]['onHand']);
    }
}
