<?php

declare(strict_types=1);

namespace App\Export;

/**
 * Writes an export data set to a file.
 */
interface ExporterInterface
{
    public function exportToFile(ExportDataset $dataset, string $filePath): void;
}
