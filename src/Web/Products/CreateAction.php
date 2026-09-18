<?php

declare(strict_types=1);

namespace App\Web\Products;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Shows the empty product form.
 */
final readonly class CreateAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductFormView $formView,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/form', [
            ...$this->formView->forProduct(),
            'errors' => [],
            'isEdit' => false,
        ]);
    }
}
