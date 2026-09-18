<?php

declare(strict_types=1);

namespace App\Products;

/**
 * A variant produced by the option combination generator, not yet stored.
 */
final readonly class GeneratedVariant
{
    /**
     * @param list<int> $optionValueIds
     */
    public function __construct(
        public string $sku,
        public array $optionValueIds,
        public bool $isDefault,
    ) {}
}
