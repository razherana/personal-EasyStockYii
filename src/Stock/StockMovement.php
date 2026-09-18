<?php

declare(strict_types=1);

namespace App\Stock;

use App\Shared\Database\Row;
use DateTimeImmutable;

/**
 * A single entry of the stock ledger.
 *
 * The stock level of a variant is the sum of the quantity changes of all its movements.
 *
 * @psalm-type StockMovementRow = array<array-key, mixed>
 */
final readonly class StockMovement
{
    public function __construct(
        public int $id,
        public int $variantId,
        public MovementType $type,
        public int $quantityChange,
        public ?float $unitCost,
        public ?string $reference,
        public ?string $note,
        public ?int $createdBy,
        public DateTimeImmutable $createdAt,
        public string $createdByName = '',
    ) {}

    /**
     * @psalm-param StockMovementRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        $unitCost = Row::nullableString($row, 'unit_cost');

        return new self(
            id: Row::int($row, 'id'),
            variantId: Row::int($row, 'variant_id'),
            type: MovementType::from(Row::string($row, 'type')),
            quantityChange: Row::int($row, 'quantity_change'),
            unitCost: $unitCost === null ? null : (float) $unitCost,
            reference: Row::nullableString($row, 'reference'),
            note: Row::nullableString($row, 'note'),
            createdBy: Row::nullableInt($row, 'created_by'),
            createdAt: Row::dateTime($row, 'created_at'),
            createdByName: Row::nullableString($row, 'created_by_name') ?? '',
        );
    }

    public function isIncoming(): bool
    {
        return $this->quantityChange > 0;
    }
}
