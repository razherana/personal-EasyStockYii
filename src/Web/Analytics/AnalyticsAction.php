<?php

declare(strict_types=1);

namespace App\Web\Analytics;

use App\Products\ProductRepository;
use App\Products\VariantRepository;
use App\Stock\StockRepository;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function max;
use function sprintf;

/**
 * Stock analytics: movement trend, stock health and the products holding the most units.
 */
final readonly class AnalyticsAction
{
    private const DAYS = 14;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductRepository $products,
        private VariantRepository $variants,
        private StockRepository $stock,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $statusCounts = $this->stock->levelStatusCounts();

        return $this->viewRenderer->render(__DIR__ . '/analytics', [
            'productCount' => $this->products->countActive(),
            'variantCount' => $this->variants->countActive(),
            'totalOnHand' => $this->stock->totalOnHand(),
            'lowStockCount' => $this->stock->countLowStockLevels(),
            'dailyTotals' => $this->dailyTotals(),
            'statusCounts' => $statusCounts,
            'statusTotal' => $statusCounts['out'] + $statusCounts['low'] + $statusCounts['healthy'],
            'topProducts' => $this->stock->topProductsByOnHand(8),
        ]);
    }

    /**
     * Movement totals per day for the last two weeks, including days without movements.
     *
     * @return array{labels: list<string>, incoming: list<int>, outgoing: list<int>, adjustment: list<int>}
     */
    private function dailyTotals(): array
    {
        $byDay = [];

        foreach ($this->stock->dailyMovementTotals(self::DAYS) as $row) {
            $byDay[$row['day']] = $row;
        }

        $labels = [];
        $incoming = [];
        $outgoing = [];
        $adjustment = [];
        $today = (new DateTimeImmutable())->setTime(0, 0);

        for ($offset = max(1, self::DAYS) - 1; $offset >= 0; $offset--) {
            $day = $today->modify(sprintf('-%d days', $offset));
            $totals = $byDay[$day->format('Y-m-d')] ?? null;

            $labels[] = $day->format('d M');
            $incoming[] = $totals['incoming'] ?? 0;
            $outgoing[] = $totals['outgoing'] ?? 0;
            $adjustment[] = $totals['adjustment'] ?? 0;
        }

        return [
            'labels' => $labels,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'adjustment' => $adjustment,
        ];
    }
}
