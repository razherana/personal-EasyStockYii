<?php

declare(strict_types=1);

namespace App\Products;

use App\Shared\Database\Row;
use DateTimeImmutable;

/**
 * A purchasable variation of a product: one combination of option values with its own SKU.
 *
 * @psalm-type ProductVariantRow = array<array-key, mixed>
 */
final readonly class ProductVariant
{
    /**
     * @param list<int> $optionValueIds
     * @param list<string> $optionLabels Human readable option values, for example "Size: XL".
     */
    public function __construct(
        public int $id,
        public int $productId,
        public string $sku,
        public bool $isDefault,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $optionValueIds = [],
        public array $optionLabels = [],
    ) {}

    /**
     * @psalm-param ProductVariantRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        return new self(
            id: Row::int($row, 'id'),
            productId: Row::int($row, 'product_id'),
            sku: Row::string($row, 'sku'),
            isDefault: Row::bool($row, 'is_default'),
            isActive: Row::bool($row, 'is_active'),
            createdAt: Row::dateTime($row, 'created_at'),
            updatedAt: Row::dateTime($row, 'updated_at'),
        );
    }

    /**
     * @param list<int> $optionValueIds
     * @param list<string> $optionLabels
     */
    public function withOptionData(array $optionValueIds, array $optionLabels): self
    {
        return new self(
            id: $this->id,
            productId: $this->productId,
            sku: $this->sku,
            isDefault: $this->isDefault,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            optionValueIds: $optionValueIds,
            optionLabels: $optionLabels,
        );
    }

    public function withActive(bool $isActive, DateTimeImmutable $updatedAt): self
    {
        return new self(
            id: $this->id,
            productId: $this->productId,
            sku: $this->sku,
            isDefault: $this->isDefault,
            isActive: $isActive,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            optionValueIds: $this->optionValueIds,
            optionLabels: $this->optionLabels,
        );
    }

    public function optionsText(): string
    {
        return $this->optionLabels === [] ? 'Default' : implode(' · ', $this->optionLabels);
    }
}
