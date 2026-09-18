<?php

declare(strict_types=1);

use App\Stock\MovementListItem;
use App\Stock\StockLevel;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var int $productCount
 * @var int $variantCount
 * @var int $totalOnHand
 * @var int $lowStockCount
 * @var list<StockLevel> $lowStockLevels
 * @var list<MovementListItem> $latestMovements
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Dashboard');
?>

<h1>Dashboard</h1>

<div class="stat-grid">
    <div class="stat">
        <span class="stat-label">Active products</span>
        <span class="stat-value"><?= $productCount ?></span>
    </div>
    <div class="stat">
        <span class="stat-label">Active variants</span>
        <span class="stat-value"><?= $variantCount ?></span>
    </div>
    <div class="stat">
        <span class="stat-label">Units in stock</span>
        <span class="stat-value"><?= $totalOnHand ?></span>
    </div>
    <div class="stat">
        <span class="stat-label">Low stock variants</span>
        <span class="stat-value"><?= $lowStockCount ?></span>
    </div>
</div>

<div class="split">
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Latest movements</h2>
            <a class="button-link" href="<?= $urlGenerator->generate('stock-list') ?>">All stock</a>
        </div>

        <?php if ($latestMovements === []): ?>
            <p class="empty-state">No stock movements recorded yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Variant</th>
                    <th class="table-numeric">Change</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latestMovements as $item): ?>
                    <tr>
                        <td class="nowrap"><?= $item->movement->createdAt->format('Y-m-d H:i') ?></td>
                        <td><?= Html::encode($item->productName) ?></td>
                        <td class="mono"><?= Html::encode($item->variantSku) ?></td>
                        <td class="table-numeric">
                            <?= $item->movement->quantityChange > 0 ? '+' : '' ?><?= $item->movement->quantityChange ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Low stock</h2>
            <a class="button-link" href="<?= $urlGenerator->generate('stock-list', ['low' => 1]) ?>">All low stock</a>
        </div>

        <?php if ($lowStockLevels === []): ?>
            <p class="empty-state">Nothing below its threshold. Good.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th class="table-numeric">On hand</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($lowStockLevels as $level): ?>
                    <tr>
                        <td><?= Html::encode($level->productName) ?></td>
                        <td class="mono"><?= Html::encode($level->variantSku) ?></td>
                        <td class="table-numeric"><?= $level->onHand ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</div>
