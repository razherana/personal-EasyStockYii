<?php

declare(strict_types=1);

namespace App\Products;

/**
 * Product list row: the product plus its active variant count and stock on hand.
 */
final readonly class ProductListItem
{
    public function __construct(
        public Product $product,
        public int $variantCount,
        public int $onHand,
    ) {}

    public function isLowStock(): bool
    {
        $threshold = $this->product->lowStockThreshold;

        return $threshold !== null && $this->onHand <= $threshold;
    }
}
