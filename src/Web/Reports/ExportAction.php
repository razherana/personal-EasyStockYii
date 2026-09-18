<?php

declare(strict_types=1);

namespace App\Web\Reports;

use App\Export\ExportDatasetName;
use App\Export\ExportFormat;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Export page: pick a data set and a format.
 */
final readonly class ExportAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/export', [
            'datasets' => ExportDatasetName::cases(),
            'formats' => ExportFormat::cases(),
        ]);
    }
}
