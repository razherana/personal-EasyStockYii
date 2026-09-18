<?php

declare(strict_types=1);

namespace App\Tests\Unit\Import;

use App\Import\CsvRowReader;
use App\Import\ImportException;
use App\Import\RowReaderFactory;
use App\Import\XlsxRowReader;
use App\Export\DatasetBuilder;
use App\Export\ExportDatasetName;
use App\Export\XlsxExporter;
use App\Tests\Support\DatabaseTestCase;

use function array_values;
use function file_put_contents;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class RowReadersTest extends DatabaseTestCase
{
    private function write(string $content, string $extension): string
    {
        $file = tempnam(sys_get_temp_dir(), 'easystock-read');

        if ($file === false) {
            self::fail('Unable to create a temporary file.');
        }

        $path = $file . '.' . $extension;

        file_put_contents($path, $content);

        return $path;
    }

    public function testCsvReaderUsesTheHeaderNames(): void
    {
        $file = $this->write("SKU, Name ,on_hand\nchair, Chair \"deluxe\" ,4\n", 'csv');

        $rows = array_values((array) iterator_to_array((new CsvRowReader())->rows($file), false));

        unlink($file);

        assertCount(1, $rows);
        assertSame('chair', $rows[0]['sku'] ?? null);
        assertSame('Chair "deluxe"', $rows[0]['name'] ?? null);
        assertSame('4', $rows[0]['on_hand'] ?? null);
    }

    public function testCsvReaderRejectsMissingFile(): void
    {
        $this->expectException(ImportException::class);

        iterator_to_array((new CsvRowReader())->rows('/does/not/exist.csv'), false);
    }

    public function testXlsxReaderReadsBackWhatTheExporterWrote(): void
    {
        $services = $this->services();
        $dataset = (new DatasetBuilder($services->products(), $services->stock(), $services->options()))
            ->build(ExportDatasetName::ImportTemplate);
        $file = tempnam(sys_get_temp_dir(), 'easystock-xlsx') . '.xlsx';

        (new XlsxExporter())->exportToFile($dataset, $file);
        $rows = array_values((array) iterator_to_array((new XlsxRowReader())->rows($file), false));
        unlink($file);

        assertCount(1, $rows);
        assertSame('TSHIRT-XL-RED', $rows[0]['sku'] ?? null);
        assertSame('Size=XL; Color=Red', $rows[0]['options'] ?? null);
    }

    public function testRowReaderFactoryPicksTheReaderByExtension(): void
    {
        $factory = new RowReaderFactory(new CsvRowReader(), new XlsxRowReader());

        self::assertInstanceOf(CsvRowReader::class, $factory->readerFor('products.csv'));
        self::assertInstanceOf(XlsxRowReader::class, $factory->readerFor('products.XLSX'));
    }

    public function testRowReaderFactoryRejectsOtherExtensions(): void
    {
        $factory = new RowReaderFactory(new CsvRowReader(), new XlsxRowReader());

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('Unsupported file type: use a .csv or .xlsx file (products.pdf).');

        $factory->readerFor('products.pdf');
    }
}
