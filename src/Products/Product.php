<?php

declare(strict_types=1);

namespace App\Products;

use App\Shared\Database\Row;
use DateTimeImmutable;

/**
 * A product of the catalogue. Stock is tracked per {@see ProductVariant}.
 *
 * @psalm-type ProductRow = array<array-key, mixed>
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public ?string $description,
        public string $unit,
        public string $qrToken,
        public ?int $lowStockThreshold,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @psalm-param ProductRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        return new self(
            id: Row::int($row, 'id'),
            sku: Row::string($row, 'sku'),
            name: Row::string($row, 'name'),
            description: Row::nullableString($row, 'description'),
            unit: Row::string($row, 'unit'),
            qrToken: Row::string($row, 'qr_token'),
            lowStockThreshold: Row::nullableInt($row, 'low_stock_threshold'),
            isActive: Row::bool($row, 'is_active'),
            createdAt: Row::dateTime($row, 'created_at'),
            updatedAt: Row::dateTime($row, 'updated_at'),
        );
    }
}
