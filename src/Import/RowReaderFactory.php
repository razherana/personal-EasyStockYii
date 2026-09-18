<?php

declare(strict_types=1);

namespace App\Import;

use function pathinfo;
use function strtolower;

use const PATHINFO_EXTENSION;

/**
 * Picks the row reader that matches the uploaded file.
 */
final readonly class RowReaderFactory
{
    public function __construct(
        private CsvRowReader $csvReader,
        private XlsxRowReader $xlsxReader,
    ) {}

    public function readerFor(string $fileName): RowReaderInterface
    {
        return match (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            'csv', 'txt' => $this->csvReader,
            'xlsx' => $this->xlsxReader,
            default => throw ImportException::unsupportedFormat($fileName),
        };
    }
}
