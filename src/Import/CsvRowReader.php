<?php

declare(strict_types=1);

namespace App\Import;

use function array_map;
use function fclose;
use function feof;
use function fgetcsv;
use function fopen;
use function is_file;
use function mb_strtolower;
use function trim;

/**
 * Reads CSV files. The first line must contain the column names.
 */
final readonly class CsvRowReader implements RowReaderInterface
{
    public function __construct(
        private string $delimiter = ',',
    ) {}

    public function rows(string $filePath): iterable
    {
        if (!is_file($filePath)) {
            throw ImportException::unreadableFile($filePath);
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw ImportException::unreadableFile($filePath);
        }

        $headers = null;
        $lineNumber = 0;

        try {
            while (!feof($handle)) {
                /** @var list<string|null>|false $cells */
                $cells = fgetcsv($handle, 0, $this->delimiter, '"', '');

                if ($cells === false) {
                    break;
                }

                if ($cells === [null]) {
                    continue;
                }

                $lineNumber++;

                if ($headers === null) {
                    $headers = $this->normalize($cells);

                    continue;
                }

                yield $lineNumber - 1 => $this->combine($headers, $cells);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param list<string|null> $cells
     *
     * @return list<string>
     */
    private function normalize(array $cells): array
    {
        return array_map(
            static fn(?string $cell): string => mb_strtolower(trim((string) $cell)),
            $cells,
        );
    }

    /**
     * @param list<string> $headers
     * @param list<string|null> $cells
     *
     * @return array<string, string>
     */
    private function combine(array $headers, array $cells): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = trim($cells[$index] ?? '');
        }

        return $row;
    }
}
