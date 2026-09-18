<?php

declare(strict_types=1);

namespace App\Stock;

use App\Shared\Database\Row;

/**
 * Stock movement with the variant and product it belongs to, for list views.
 *
 * @psalm-type MovementListRow = array<array-key, mixed>
 */
final readonly class MovementListItem
{
    public function __construct(
        public StockMovement $movement,
        public int $productId,
        public string $productName,
        public string $variantSku,
    ) {}

    /**
     * @psalm-param MovementListRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        return new self(
            movement: StockMovement::hydrate($row),
            productId: Row::int($row, 'product_id'),
            productName: Row::string($row, 'product_name'),
            variantSku: Row::string($row, 'variant_sku'),
        );
    }
}
