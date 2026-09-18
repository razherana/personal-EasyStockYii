<?php

declare(strict_types=1);

namespace App\Export;

use RuntimeException;
use Yiisoft\Aliases\Aliases;

use function file_exists;
use function glob;
use function is_dir;
use function mkdir;
use function time;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const GLOB_NOSORT;

/**
 * Builds export data sets and writes them to temporary files.
 */
final readonly class ExportService
{
    private const DIRECTORY = 'exports';
    private const MAX_TEMP_FILE_AGE = 3600;

    public function __construct(
        private DatasetBuilder $datasets,
        private CsvExporter $csvExporter,
        private XlsxExporter $xlsxExporter,
        private PdfExporter $pdfExporter,
        private Aliases $aliases,
    ) {}

    public function dataset(ExportDatasetName $name): ExportDataset
    {
        return $this->datasets->build($name);
    }

    /**
     * Writes the data set and returns the path of the created file.
     */
    public function exportToFile(ExportDataset $dataset, ExportFormat $format): string
    {
        $directory = $this->directory();

        $this->pruneOldFiles($directory);

        $filePath = $directory . DIRECTORY_SEPARATOR
            . $dataset->fileBaseName . '-' . date('Ymd-His') . '.' . $format->extension();

        $this->exporter($format)->exportToFile($dataset, $filePath);

        return $filePath;
    }

    public function suggestedFileName(ExportDataset $dataset, ExportFormat $format): string
    {
        return $dataset->fileBaseName . '-' . date('Ymd-His') . '.' . $format->extension();
    }

    private function exporter(ExportFormat $format): ExporterInterface
    {
        return match ($format) {
            ExportFormat::Csv => $this->csvExporter,
            ExportFormat::Xlsx => $this->xlsxExporter,
            ExportFormat::Pdf => $this->pdfExporter,
        };
    }

    private function directory(): string
    {
        $directory = $this->aliases->get('@runtime') . DIRECTORY_SEPARATOR . self::DIRECTORY;

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create export directory \"$directory\".");
        }

        return $directory;
    }

    private function pruneOldFiles(string $directory): void
    {
        $limit = time() - self::MAX_TEMP_FILE_AGE;

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT) ?: [] as $file) {
            if (file_exists($file) && filemtime($file) < $limit) {
                unlink($file);
            }
        }
    }
}
