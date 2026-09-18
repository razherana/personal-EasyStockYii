<?php

declare(strict_types=1);

namespace App\Web\PublicProduct;

use App\Products\ProductRepository;
use App\Stock\StockRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Public product summary reachable through the QR code. No authentication.
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductRepository $products,
        private StockRepository $stock,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $token = (string) $this->currentRoute->getArgument('token');
        $product = $this->products->findByQrToken($token);

        if ($product === null) {
            return $this->viewRenderer
                ->withLayout('@src/Web/Shared/Layout/Public/layout.php')
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Public/layout.php')
            ->render(__DIR__ . '/product', [
                'product' => $product,
                'levels' => $this->stock->levelsForProduct($product->id),
            ]);
    }
}
