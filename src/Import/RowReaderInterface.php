<?php

declare(strict_types=1);

namespace App\Import;

/**
 * Reads a spreadsheet file as an iterable of rows keyed by their header name.
 */
interface RowReaderInterface
{
    /**
     * @return iterable<int, array<string, string>> Row number (1 based, header excluded) to column values.
     */
    public function rows(string $filePath): iterable;
}
