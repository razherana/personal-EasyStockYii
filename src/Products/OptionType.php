<?php

declare(strict_types=1);

namespace App\Products;

use App\Shared\Database\Row;

/**
 * An option type, for example "Size" or "Color".
 *
 * @psalm-type OptionTypeRow = array<array-key, mixed>
 */
final readonly class OptionType
{
    public function __construct(
        public int $id,
        public string $name,
        public int $position,
    ) {}

    /**
     * @psalm-param OptionTypeRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        return new self(
            id: Row::int($row, 'id'),
            name: Row::string($row, 'name'),
            position: Row::int($row, 'position'),
        );
    }
}
