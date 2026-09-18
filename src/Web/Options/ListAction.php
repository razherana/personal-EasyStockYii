<?php

declare(strict_types=1);

namespace App\Web\Options;

use App\Products\OptionRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Option types and their values: the building blocks of product variants.
 */
final readonly class ListAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private OptionRepository $options,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/list', [
            'types' => $this->options->findTypes(),
            'values' => $this->options->findValuesGroupedByType(),
        ]);
    }
}
