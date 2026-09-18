<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\ProductRepository;
use App\Products\ProductVariant;
use App\Products\VariantRepository;
use App\Stock\StockRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Product detail: product data, variants with their stock level and the QR code.
 */
final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductRepository $products,
        private VariantRepository $variants,
        private StockRepository $stock,
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

        $variantsById = [];

        foreach ($this->variants->findByProductId($product->id) as $variant) {
            $variantsById[$variant->id] = $variant;
        }

        return $this->viewRenderer->render(__DIR__ . '/view', [
            'product' => $product,
            'levels' => $this->stock->levelsForProduct($product->id),
            /** @var array<int, ProductVariant> $variantsById */
            'variantsById' => $variantsById,
        ]);
    }
}
