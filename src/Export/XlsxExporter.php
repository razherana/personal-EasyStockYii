<?php

declare(strict_types=1);

namespace App\Export;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes a data set as an XLSX workbook using a streaming writer.
 */
final readonly class XlsxExporter implements ExporterInterface
{
    public function exportToFile(ExportDataset $dataset, string $filePath): void
    {
        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues($dataset->headers));

        foreach ($dataset->rowsInHeaderOrder() as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }
}
