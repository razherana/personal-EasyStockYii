<?php

declare(strict_types=1);

namespace App\Web\Reports;

use App\Import\ImportResult;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Import page with the upload form.
 */
final readonly class ImportAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/import', [
            'result' => ImportResult::empty(),
        ]);
    }
}
