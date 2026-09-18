<?php

declare(strict_types=1);

namespace App\Stock;

/**
 * Kinds of stock movements.
 *
 * `In` and `Out` take a positive quantity and are stored as signed changes.
 * `Adjustment` takes a signed change and is used for corrections.
 */
enum MovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stock in',
            self::Out => 'Stock out',
            self::Adjustment => 'Adjustment',
        };
    }

    /**
     * Converts the entered positive quantity into the signed change stored in the ledger.
     */
    public function toQuantityChange(int $quantity): int
    {
        return $this === self::Out ? -$quantity : $quantity;
    }
}
