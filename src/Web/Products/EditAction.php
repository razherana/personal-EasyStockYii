<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\ProductRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Shows the product form with the current values.
 */
final readonly class EditAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductRepository $products,
        private ProductFormView $formView,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $product = $this->products->findById((int) $this->currentRoute->getArgument('id'));

        if ($product === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        return $this->viewRenderer->render(__DIR__ . '/form', [
            ...$this->formView->forProduct($product),
            'product' => $product,
            'errors' => [],
            'isEdit' => true,
        ]);
    }
}
