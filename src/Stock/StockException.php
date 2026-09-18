<?php

declare(strict_types=1);

namespace App\Stock;

use RuntimeException;

use function sprintf;

/**
 * Domain errors raised by {@see StockService}.
 */
final class StockException extends RuntimeException
{
    public static function quantityMustBePositive(string $type): self
    {
        return new self(sprintf('Enter a quantity greater than zero for a "%s" movement.', $type));
    }

    public static function quantityMustNotBeZero(): self
    {
        return new self('Enter a quantity change that is not zero.');
    }

    public static function insufficientStock(int $onHand): self
    {
        return new self(sprintf('Not enough stock: only %d available.', $onHand));
    }
}
