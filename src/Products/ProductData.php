<?php

declare(strict_types=1);

namespace App\Products;

/**
 * Values needed to create or update a product.
 */
final readonly class ProductData
{
    /**
     * @param list<int> $optionValueIds
     */
    public function __construct(
        public string $sku,
        public string $name,
        public string $unit = 'piece',
        public ?string $description = null,
        public ?int $lowStockThreshold = null,
        public bool $isActive = true,
        public array $optionValueIds = [],
    ) {}
}
