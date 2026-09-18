<?php

declare(strict_types=1);

namespace App\Products;

use App\Shared\Database\Row;

/**
 * A value of an option type, for example "XL" for "Size".
 *
 * @psalm-type OptionValueRow = array<array-key, mixed>
 */
final readonly class OptionValue
{
    public function __construct(
        public int $id,
        public int $optionTypeId,
        public string $value,
        public int $position,
    ) {}

    /**
     * @psalm-param OptionValueRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        return new self(
            id: Row::int($row, 'id'),
            optionTypeId: Row::int($row, 'option_type_id'),
            value: Row::string($row, 'value'),
            position: Row::int($row, 'position'),
        );
    }
}
