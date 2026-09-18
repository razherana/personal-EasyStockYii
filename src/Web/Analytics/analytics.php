<?php

declare(strict_types=1);

use App\Access\Permission;
use App\User\CurrentUserProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var int $productCount
 * @var int $variantCount
 * @var int $totalOnHand
 * @var int $lowStockCount
 * @var array{labels: list<string>, incoming: list<int>, outgoing: list<int>, adjustment: list<int>} $dailyTotals
 * @var array{out: int, low: int, healthy: int} $statusCounts
 * @var int $statusTotal
 * @var list<array{name: string, onHand: int}> $topProducts
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Analytics');

// Chart.js is registered at the end of the body, right before the initialisation script below.
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js');

$this->registerJsVar('easystockAnalytics', [
    'labels' => $dailyTotals['labels'],
    'incoming' => $dailyTotals['incoming'],
    'outgoing' => $dailyTotals['outgoing'],
    'adjustment' => $dailyTotals['adjustment'],
    'status' => [$statusCounts['healthy'], $statusCounts['low'], $statusCounts['out']],
    'statusLabels' => ['Healthy', 'Low stock', 'Out of stock'],
]);

$this->registerJs(<<<'JS'
(function () {
    const data = window.easystockAnalytics;

    if (typeof Chart === 'undefined' || !data) {
        return;
    }

    Chart.defaults.font.family = '"Plus Jakarta Sans", system-ui, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#98a2b3';

    const grid = { color: '#eef1f6', drawTicks: false };
    const legend = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, padding: 18 } };
    const tooltip = { backgroundColor: '#16181d', padding: 12, cornerRadius: 10 };

    const trend = document.getElementById('movement-trend');
    if (trend) {
        new Chart(trend, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Stock in',
                        data: data.incoming,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Stock out',
                        data: data.outgoing,
                        borderColor: '#f04438',
                        backgroundColor: 'rgba(240, 68, 56, 0.10)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Adjustments',
                        data: data.adjustment,
                        borderColor: '#7a5af8',
                        borderWidth: 2,
                        borderDash: [6, 4],
                        tension: 0.35,
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: legend, tooltip: tooltip },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 8 } },
                    y: { beginAtZero: true, grid: grid, border: { display: false }, ticks: { precision: 0 } }
                }
            }
        });
    }

    const health = document.getElementById('stock-health');
    if (health) {
        new Chart(health, {
            type: 'doughnut',
            data: {
                labels: data.statusLabels,
                datasets: [{
                    data: data.status,
                    backgroundColor: ['#22c55e', '#f79009', '#f04438'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: legend, tooltip: tooltip }
            }
        });
    }
})();
JS);

$statusShare = static fn(int $count): float => $statusTotal > 0 ? round($count / $statusTotal * 100, 1) : 0.0;
$maxOnHand = 1;

foreach ($topProducts as $product) {
    $maxOnHand = max($maxOnHand, $product['onHand']);
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Analytics</h1>
        <p class="page-subtitle">
            Movement trend, stock health and the products holding the most units.
        </p>
    </div>
    <?php if ($currentUser->can(Permission::Export)): ?>
        <div class="page-actions">
            <a class="button button-quiet" href="<?= $urlGenerator->generate('report-export') ?>">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                Export data
            </a>
        </div>
    <?php endif ?>
</div>

<div class="stat-strip">
    <div class="stat-cell">
        <span class="stat-eyebrow"><i class="fa-solid fa-cube" aria-hidden="true"></i> Active products</span>
        <span class="stat-value"><?= $productCount ?><small>products</small></span>
        <span class="stat-foot">Available in the catalogue</span>
    </div>
    <div class="stat-cell">
        <span class="stat-eyebrow"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Active variants</span>
        <span class="stat-value"><?= $variantCount ?><small>variants</small></span>
        <span class="stat-foot">Sellable combinations</span>
    </div>
    <div class="stat-cell">
        <span class="stat-eyebrow"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> Units in hand</span>
        <span class="stat-value"><?= $totalOnHand ?><small>items</small></span>
        <span class="stat-foot">Sum of every movement</span>
    </div>
    <div class="stat-cell">
        <span class="stat-eyebrow">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Low stock
        </span>
        <span class="stat-value"><?= $lowStockCount ?><small>variants</small></span>
        <span class="stat-foot">
            <?php if ($lowStockCount > 0): ?>
                <span class="badge badge-warning">Needs attention</span>
            <?php else: ?>
                <span class="badge badge-success">All good</span>
            <?php endif ?>
        </span>
    </div>
</div>

<div class="chart-grid">
    <div class="panel panel-wide">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                    Movement trend
                </h2>
                <p class="panel-subtitle">Units booked in, out and adjusted per day over the last 14 days.</p>
            </div>
        </div>
        <div class="chart-canvas">
            <canvas id="movement-trend"
                    role="img"
                    aria-label="Line chart of daily stock movements for the last 14 days"></canvas>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-heart-pulse" aria-hidden="true"></i>
                    Stock health
                </h2>
                <p class="panel-subtitle">Active variants by stock status.</p>
            </div>
        </div>

        <?php if ($statusTotal === 0): ?>
            <p class="empty-state">
                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                <span>No active variants yet.</span>
            </p>
        <?php else: ?>
            <div class="chart-canvas chart-canvas-sm">
                <canvas id="stock-health"
                        role="img"
                        aria-label="Doughnut chart of healthy, low and out of stock variants"></canvas>
            </div>

            <ul class="list">
                <li class="list-item">
                    <span class="list-icon list-icon-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                    <span class="list-main">
                        <span class="list-title">Healthy</span>
                        <span class="list-sub">Above their low stock threshold</span>
                    </span>
                    <span class="list-value"><?= $statusCounts['healthy'] ?> · <?= $statusShare($statusCounts['healthy']) ?>%</span>
                </li>
                <li class="list-item">
                    <span class="list-icon list-icon-warning"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                    <span class="list-main">
                        <span class="list-title">Low stock</span>
                        <span class="list-sub">At or below the threshold</span>
                    </span>
                    <span class="list-value"><?= $statusCounts['low'] ?> · <?= $statusShare($statusCounts['low']) ?>%</span>
                </li>
                <li class="list-item">
                    <span class="list-icon list-icon-danger"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>
                    <span class="list-main">
                        <span class="list-title">Out of stock</span>
                        <span class="list-sub">Nothing left to sell</span>
                    </span>
                    <span class="list-value"><?= $statusCounts['out'] ?> · <?= $statusShare($statusCounts['out']) ?>%</span>
                </li>
            </ul>
        <?php endif ?>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    Top products
                </h2>
                <p class="panel-subtitle">Products with the most units in hand.</p>
            </div>
            <a class="button-link" href="<?= $urlGenerator->generate('stock-list') ?>">All stock</a>
        </div>

        <?php if ($topProducts === []): ?>
            <p class="empty-state">
                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                <span>No stock on hand yet.</span>
            </p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($topProducts as $product): ?>
                    <li class="list-item">
                        <span class="list-main list-main-grow">
                            <span class="list-title"><?= Html::encode($product['name']) ?></span>
                            <span class="progress">
                                <span class="progress-bar"
                                      style="display:block; width: <?= (int) round($product['onHand'] / $maxOnHand * 100) ?>%"></span>
                            </span>
                        </span>
                        <span class="list-value"><?= $product['onHand'] ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</div>
