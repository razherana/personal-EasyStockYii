<?php

declare(strict_types=1);

use App\Access\Permission;
use App\Stock\MovementListItem;
use App\Stock\StockLevel;
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
 * @var list<StockLevel> $lowStockLevels
 * @var list<MovementListItem> $latestMovements
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Dashboard');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Catalogue, stock levels and the latest ledger entries.</p>
    </div>
    <div class="page-actions">
        <?php if ($currentUser->can(Permission::StockView)): ?>
            <a class="button button-quiet" href="<?= $urlGenerator->generate('analytics') ?>">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                Analytics
            </a>
        <?php endif ?>
        <?php if ($currentUser->can(Permission::StockOperate)): ?>
            <a class="button" href="<?= $urlGenerator->generate('stock-list') ?>">
                <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                Stock levels
            </a>
        <?php endif ?>
    </div>
</div>

<div class="stat-strip">
    <div class="stat-cell">
        <span class="stat-eyebrow">
            <i class="fa-solid fa-cube" aria-hidden="true"></i>
            Active products
        </span>
        <span class="stat-value"><?= $productCount ?><small>products</small></span>
        <span class="stat-foot">Available in the catalogue</span>
    </div>
    <div class="stat-cell">
        <span class="stat-eyebrow">
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
            Active variants
        </span>
        <span class="stat-value"><?= $variantCount ?><small>variants</small></span>
        <span class="stat-foot">Tracked combinations</span>
    </div>
    <div class="stat-cell">
        <span class="stat-eyebrow">
            <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
            Units in stock
        </span>
        <span class="stat-value"><?= $totalOnHand ?><small>items</small></span>
        <span class="stat-foot">Sum of every movement</span>
    </div>
    <div class="stat-cell">
        <span class="stat-eyebrow">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            Low stock variants
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

<div class="split">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                    Latest movements
                </h2>
                <p class="panel-subtitle">The most recent entries in the stock ledger.</p>
            </div>
            <a class="button-link" href="<?= $urlGenerator->generate('stock-list') ?>">All stock</a>
        </div>

        <?php if ($latestMovements === []): ?>
            <p class="empty-state">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                <span>No stock movements recorded yet.</span>
            </p>
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
                        <td class="nowrap faint"><?= $item->movement->createdAt->format('Y-m-d H:i') ?></td>
                        <td>
                            <span class="cell-title"><?= Html::encode($item->productName) ?></span>
                        </td>
                        <td><span class="mono faint nowrap"><?= Html::encode($item->variantSku) ?></span></td>
                        <td class="table-numeric">
                            <?php $change = $item->movement->quantityChange; ?>
                            <span class="badge <?= $change >= 0 ? 'badge-success' : 'badge-danger' ?>">
                                <?= $change > 0 ? '+' : '' ?><?= $change ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    Low stock
                </h2>
                <p class="panel-subtitle">Variants at or below their threshold.</p>
            </div>
            <a class="button-link" href="<?= $urlGenerator->generate('stock-list', ['low' => '1']) ?>">
                All low stock
            </a>
        </div>

        <?php if ($lowStockLevels === []): ?>
            <p class="empty-state">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span>Nothing below its threshold. Good.</span>
            </p>
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
                        <td>
                            <a class="cell-title"
                               href="<?= $urlGenerator->generate('product-view', ['id' => $level->productId]) ?>">
                                <?= Html::encode($level->productName) ?>
                            </a>
                        </td>
                        <td><span class="mono faint"><?= Html::encode($level->variantSku) ?></span></td>
                        <td class="table-numeric">
                            <span class="badge badge-warning"><?= $level->onHand ?></span>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</div>
