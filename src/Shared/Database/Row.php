<?php

declare(strict_types=1);

namespace App\Shared\Database;

use DateTimeImmutable;
use UnexpectedValueException;

use function array_key_exists;
use function sprintf;

/**
 * Reads typed values out of a database row.
 *
 * SQLite returns all values as strings (or null), so casting happens here instead of in every
 * repository or entity.
 */
final class Row
{
    /**
     * @param array<array-key, mixed> $row
     */
    public static function int(array $row, string $key): int
    {
        /** @var int|string|bool|float|null $value */
        $value = self::value($row, $key);

        if ($value === null) {
            throw new UnexpectedValueException(sprintf('Column "%s" is null, integer expected.', $key));
        }

        return (int) $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function nullableInt(array $row, string $key): ?int
    {
        /** @var int|string|bool|float|null $value */
        $value = self::value($row, $key);

        return $value === null ? null : (int) $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function string(array $row, string $key): string
    {
        /** @var int|string|bool|float|null $value */
        $value = self::value($row, $key);

        if ($value === null) {
            throw new UnexpectedValueException(sprintf('Column "%s" is null, string expected.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function nullableString(array $row, string $key): ?string
    {
        /** @var int|string|bool|float|null $value */
        $value = self::value($row, $key);

        return $value === null ? null : (string) $value;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function bool(array $row, string $key): bool
    {
        return self::int($row, $key) === 1;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public static function dateTime(array $row, string $key): DateTimeImmutable
    {
        return Timestamps::parse(self::string($row, $key));
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private static function value(array $row, string $key): mixed
    {
        if (!array_key_exists($key, $row)) {
            throw new UnexpectedValueException(sprintf('Column "%s" is missing in the result row.', $key));
        }

        return $row[$key];
    }
}
