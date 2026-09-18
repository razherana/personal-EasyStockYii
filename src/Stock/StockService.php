<?php

declare(strict_types=1);

namespace App\Stock;

use App\Shared\Database\Timestamps;
use Yiisoft\Db\Connection\ConnectionInterface;

use function abs;
use function trim;

/**
 * Records stock movements after checking the domain rules.
 */
final readonly class StockService
{
    public function __construct(
        private StockRepository $stock,
        private ConnectionInterface $db,
    ) {}

    /**
     * @param int $quantity Positive quantity for in/out movements, signed change for adjustments.
     *
     * @throws StockException When the quantity is invalid or stock would become negative.
     */
    public function record(
        MovementType $type,
        int $variantId,
        int $quantity,
        ?string $reference,
        ?string $note,
        ?float $unitCost,
        int $userId,
    ): StockMovement {
        $quantityChange = $this->validate($type, $variantId, $quantity);

        $movement = new StockMovement(
            id: 0,
            variantId: $variantId,
            type: $type,
            quantityChange: $quantityChange,
            unitCost: $unitCost,
            reference: $this->nullable($reference),
            note: $this->nullable($note),
            createdBy: $userId,
            createdAt: Timestamps::now(),
        );

        $id = $this->db->transaction(fn(): int => $this->stock->insert($movement));

        return new StockMovement(
            id: $id,
            variantId: $movement->variantId,
            type: $movement->type,
            quantityChange: $movement->quantityChange,
            unitCost: $movement->unitCost,
            reference: $movement->reference,
            note: $movement->note,
            createdBy: $movement->createdBy,
            createdAt: $movement->createdAt,
        );
    }

    /**
     * @throws StockException
     */
    private function validate(MovementType $type, int $variantId, int $quantity): int
    {
        if ($quantity === 0) {
            throw StockException::quantityMustNotBeZero();
        }

        if ($type !== MovementType::Adjustment && $quantity < 0) {
            throw StockException::quantityMustBePositive($type->value);
        }

        $quantityChange = $type->toQuantityChange($quantity);
        $level = $this->stock->levelForVariant($variantId);

        if ($quantityChange < 0 && $level + $quantityChange < 0) {
            throw StockException::insufficientStock($level);
        }

        return $quantityChange;
    }

    private function nullable(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Absolute quantity, used by the UI to describe a movement.
     */
    public static function displayQuantity(int $quantityChange): int
    {
        return abs($quantityChange);
    }
}
