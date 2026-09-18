<?php

declare(strict_types=1);

namespace App\Web\Dashboard;

use App\Products\ProductRepository;
use App\Products\VariantRepository;
use App\Stock\StockRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Landing page: catalogue and stock summary.
 */
final readonly class DashboardAction
{
    private const PREVIEW_LIMIT = 5;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductRepository $products,
        private VariantRepository $variants,
        private StockRepository $stock,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/dashboard', [
            'productCount' => $this->products->countActive(),
            'variantCount' => $this->variants->countActive(),
            'totalOnHand' => $this->stock->totalOnHand(),
            'lowStockCount' => $this->stock->countLowStockLevels(),
            'lowStockLevels' => $this->stock->levels('', true, false, self::PREVIEW_LIMIT),
            'latestMovements' => $this->stock->latestMovementItems(self::PREVIEW_LIMIT),
        ]);
    }
}
