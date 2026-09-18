<?php

declare(strict_types=1);

namespace App\Stock;

use App\Shared\Database\Row;
use DateTimeImmutable;

/**
 * Stock level of a single variant, as shown in lists and on the public product page.
 *
 * @psalm-type StockLevelRow = array<array-key, mixed>
 */
final readonly class StockLevel
{
    public function __construct(
        public int $variantId,
        public string $variantSku,
        public bool $isDefaultVariant,
        public bool $isActiveVariant,
        public int $productId,
        public string $productName,
        public string $productSku,
        public string $productUnit,
        public bool $isActiveProduct,
        public ?int $lowStockThreshold,
        public int $onHand,
        public ?DateTimeImmutable $lastMovementAt,
    ) {}

    /**
     * @psalm-param StockLevelRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        $lastMovementAt = Row::nullableString($row, 'last_movement_at');

        return new self(
            variantId: Row::int($row, 'variant_id'),
            variantSku: Row::string($row, 'variant_sku'),
            isDefaultVariant: Row::bool($row, 'is_default_variant'),
            isActiveVariant: Row::bool($row, 'is_active_variant'),
            productId: Row::int($row, 'product_id'),
            productName: Row::string($row, 'product_name'),
            productSku: Row::string($row, 'product_sku'),
            productUnit: Row::string($row, 'product_unit'),
            isActiveProduct: Row::bool($row, 'is_active_product'),
            lowStockThreshold: Row::nullableInt($row, 'low_stock_threshold'),
            onHand: (int) (Row::nullableString($row, 'on_hand') ?? '0'),
            lastMovementAt: $lastMovementAt === null
                ? null
                : Row::dateTime($row, 'last_movement_at'),
        );
    }

    public function isLowStock(): bool
    {
        return $this->lowStockThreshold !== null && $this->onHand <= $this->lowStockThreshold;
    }
}
