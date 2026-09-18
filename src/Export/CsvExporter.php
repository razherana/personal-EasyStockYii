<?php

declare(strict_types=1);

namespace App\Export;

use RuntimeException;

use function fclose;
use function fputcsv;
use function fopen;
use function fwrite;

/**
 * Writes a data set as CSV.
 *
 * A UTF-8 byte order mark is written first so spreadsheets open the file with the right encoding.
 */
final readonly class CsvExporter implements ExporterInterface
{
    public function exportToFile(ExportDataset $dataset, string $filePath): void
    {
        $handle = fopen($filePath, 'w');

        if ($handle === false) {
            throw new RuntimeException("Unable to open \"$filePath\" for writing.");
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $dataset->headers, ',', '"', '');

        foreach ($dataset->rowsInHeaderOrder() as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }

        fclose($handle);
    }
}
