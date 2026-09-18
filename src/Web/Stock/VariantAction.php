<?php

declare(strict_types=1);

namespace App\Web\Stock;

use App\Products\ProductRepository;
use App\Products\VariantRepository;
use App\Stock\StockRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Variant detail: current level, movement form and the movement history.
 */
final readonly class VariantAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private VariantRepository $variants,
        private ProductRepository $products,
        private StockRepository $stock,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $variant = $this->variants->findById((int) $this->currentRoute->getArgument('id'));

        if ($variant === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        $product = $this->products->findById($variant->productId);

        if ($product === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        return $this->viewRenderer->render(__DIR__ . '/variant', [
            'product' => $product,
            'variant' => $variant,
            'level' => $this->stock->levelOfVariant($variant->id),
            'movements' => $this->stock->movementsForVariant($variant->id, 50),
        ]);
    }
}
