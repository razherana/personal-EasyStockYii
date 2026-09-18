<?php

declare(strict_types=1);

namespace App\Tests\Unit\Export;

use App\Export\CsvExporter;
use App\Export\ExportDataset;
use App\Export\PdfExporter;
use App\Export\XlsxExporter;
use Codeception\Test\Unit;
use OpenSpout\Reader\XLSX\Reader;

use function file_get_contents;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringStartsWith;
use function PHPUnit\Framework\assertTrue;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ExportersTest extends Unit
{
    private function dataset(): ExportDataset
    {
        return new ExportDataset(
            title: 'Stock levels',
            fileBaseName: 'stock',
            headers: ['sku', 'name', 'on_hand'],
            rows: [
                ['sku' => 'CHAIR', 'name' => 'Chair "deluxe"', 'on_hand' => 4],
                ['sku' => 'TABLE', 'name' => 'Table, 5ft', 'on_hand' => 0],
            ],
        );
    }

    private function tempFile(string $extension): string
    {
        $file = tempnam(sys_get_temp_dir(), 'easystock-test');

        return ($file === false ? '' : $file) . '.' . $extension;
    }

    public function testCsvExporterWritesHeadersAndRows(): void
    {
        $file = $this->tempFile('csv');
        (new CsvExporter())->exportToFile($this->dataset(), $file);

        $content = (string) file_get_contents($file);

        assertStringStartsWith("\xEF\xBB\xBF", $content);
        assertStringContainsString('sku,name,on_hand', $content);
        assertStringContainsString('"Chair ""deluxe"""', $content);
        assertStringContainsString('"Table, 5ft"', $content);

        unlink($file);
    }

    public function testXlsxExporterWritesAReadableWorkbook(): void
    {
        $file = $this->tempFile('xlsx');
        (new XlsxExporter())->exportToFile($this->dataset(), $file);

        $reader = new Reader();
        $reader->open($file);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();
        unlink($file);

        assertSame('sku', $rows[0][0]);
        assertSame('CHAIR', $rows[1][0]);
        assertSame('Table, 5ft', $rows[2][1]);
    }

    public function testPdfExporterWritesAPdfFile(): void
    {
        $file = $this->tempFile('pdf');
        $exporter = new PdfExporter();

        $html = $exporter->html($this->dataset());

        assertStringContainsString('Stock levels', $html);
        assertStringContainsString('Chair &quot;deluxe&quot;', $html);

        $exporter->exportToFile($this->dataset(), $file);
        $content = (string) file_get_contents($file);

        assertStringStartsWith('%PDF-', $content);
        assertTrue(strlen($content) > 1000);

        unlink($file);
    }
}
