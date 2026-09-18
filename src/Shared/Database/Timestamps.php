<?php

declare(strict_types=1);

namespace App\Shared\Database;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Converts between `DateTimeImmutable` values and the `Y-m-d H:i:s` strings stored in the database.
 *
 * All timestamps are handled in UTC.
 */
final class Timestamps
{
    public const FORMAT = 'Y-m-d H:i:s';

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timeZone());
    }

    public static function parse(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, self::timeZone());
    }

    public static function format(DateTimeImmutable $value): string
    {
        return $value->setTimezone(self::timeZone())->format(self::FORMAT);
    }

    private static function timeZone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}
