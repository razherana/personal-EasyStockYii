<?php

declare(strict_types=1);

namespace App\Export;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

use function file_put_contents;
use function htmlspecialchars;
use function implode;
use function sprintf;

use const ENT_QUOTES;

/**
 * Writes a data set as a PDF table with dompdf.
 *
 * The markup is kept free of colours and rounded corners to match the printed shelf labels.
 */
final readonly class PdfExporter implements ExporterInterface
{
    public function exportToFile(ExportDataset $dataset, string $filePath): void
    {
        $options = new Options();
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($dataset), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        if (file_put_contents($filePath, $dompdf->output()) === false) {
            throw new RuntimeException("Unable to write \"$filePath\".");
        }
    }

    public function html(ExportDataset $dataset): string
    {
        $head = [];
        $head[] = '<th>#</th>';

        foreach ($dataset->headers as $header) {
            $head[] = '<th>' . $this->escape($header) . '</th>';
        }

        $body = [];
        $number = 0;

        foreach ($dataset->rowsInHeaderOrder() as $row) {
            $number++;
            $cells = ['<td class="num">' . $number . '</td>'];

            foreach ($row as $value) {
                $cells[] = '<td>' . $this->escape($value === null ? '' : (string) $value) . '</td>';
            }

            $body[] = '<tr>' . implode('', $cells) . '</tr>';
        }

        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><style>%s</style></head><body>'
            . '<h1>%s</h1><p class="meta">%d row(s)</p>'
            . '<table><thead><tr>%s</tr></thead><tbody>%s</tbody></table>'
            . '</body></html>',
            $this->css(),
            $this->escape($dataset->title),
            count($dataset->rows),
            implode('', $head),
            implode('', $body),
        );
    }

    private function css(): string
    {
        return 'body{font-family:"DejaVu Sans",sans-serif;font-size:9px;color:#111}'
            . 'h1{font-size:16px;margin:0 0 4px}.meta{color:#555;margin:0 0 10px}'
            . 'table{width:100%;border-collapse:collapse}'
            . 'th,td{border:1px solid #999;padding:4px 6px;text-align:left}'
            . 'th{background:#eee;text-transform:uppercase;font-size:8px;letter-spacing:.05em}'
            . '.num{text-align:right;width:28px;color:#666}';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
