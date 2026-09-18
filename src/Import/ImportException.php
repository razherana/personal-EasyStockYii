<?php

declare(strict_types=1);

namespace App\Import;

use RuntimeException;

/**
 * Raised when an import file cannot be read.
 */
final class ImportException extends RuntimeException
{
    public static function unreadableFile(string $reason): self
    {
        return new self('The import file could not be read: ' . $reason);
    }

    public static function unsupportedFormat(string $fileName): self
    {
        return new self('Unsupported file type: use a .csv or .xlsx file (' . $fileName . ').');
    }

    public static function missingColumns(string ...$columns): self
    {
        return new self('Missing column(s): ' . implode(', ', $columns) . '.');
    }
}
