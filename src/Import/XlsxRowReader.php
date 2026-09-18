<?php

declare(strict_types=1);

namespace App\Import;

use OpenSpout\Reader\XLSX\Reader;

use function array_map;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function mb_strtolower;
use function trim;

/**
 * Reads XLSX files. The first line of the first sheet must contain the column names.
 */
final readonly class XlsxRowReader implements RowReaderInterface
{
    public function rows(string $filePath): iterable
    {
        $reader = new Reader();

        try {
            $reader->open($filePath);

            foreach ($reader->getSheetIterator() as $sheet) {
                $headers = null;
                $rowNumber = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    /** @var list<string> $cells */
                    $cells = array_map(
                        self::toString(...),
                        $row->toArray(),
                    );

                    $rowNumber++;

                    if ($headers === null) {
                        $headers = array_map(
                            static fn(string $header): string => mb_strtolower(trim($header)),
                            $cells,
                        );

                        continue;
                    }

                    $values = [];

                    foreach ($headers as $index => $header) {
                        $values[$header] = trim($cells[$index] ?? '');
                    }

                    yield $rowNumber - 1 => $values;
                }

                break;
            }
        } finally {
            $reader->close();
        }
    }

    private static function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }
}
