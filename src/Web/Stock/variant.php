<?php

declare(strict_types=1);

use App\Access\Permission;
use App\Products\Product;
use App\Products\ProductVariant;
use App\Stock\MovementType;
use App\Stock\StockLevel;
use App\Stock\StockMovement;
use App\User\CurrentUserProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var Product $product
 * @var ProductVariant $variant
 * @var StockLevel|null $level
 * @var list<StockMovement> $movements
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Stock · ' . $variant->sku);

$canOperate = $currentUser->can(Permission::StockOperate);
$onHand = $level?->onHand ?? 0;
$csrf = Html::encode($csrf ?? '');
$isLow = $level?->isLowStock() ?? false;
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= $urlGenerator->generate('stock-list') ?>">Stock</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <a href="<?= $urlGenerator->generate('product-view', ['id' => $product->id]) ?>">
        <?= Html::encode($product->name) ?>
    </a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span class="faint mono"><?= Html::encode($variant->sku) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= Html::encode($variant->sku) ?></h1>
        <div class="product-meta">
            <?php foreach ($variant->optionLabels as $label): ?>
                <span class="badge badge-info"><?= Html::encode($label) ?></span>
            <?php endforeach ?>
            <?php if ($variant->isDefault): ?>
                <span class="badge">default variant</span>
            <?php endif ?>
            <?php if (!$variant->isActive): ?>
                <span class="badge">inactive</span>
            <?php endif ?>
        </div>
    </div>

    <div class="stat">
        <span class="stat-label">On hand</span>
        <span class="stat-value"><?= $onHand ?></span>
        <span class="stat-foot">
            <?php if ($onHand <= 0): ?>
                <span class="badge badge-danger">Out of stock</span>
            <?php elseif ($isLow): ?>
                <span class="badge badge-warning">Low stock</span>
            <?php else: ?>
                <span class="badge badge-success">Healthy</span>
            <?php endif ?>
        </span>
    </div>
</div>

<div class="split">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    Movement history
                </h2>
                <p class="panel-subtitle">Newest first; the level is the sum of every entry.</p>
            </div>
        </div>

        <?php if ($movements === []): ?>
            <p class="empty-state">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                <span>No movements recorded for this variant yet.</span>
            </p>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th class="table-numeric">Change</th>
                    <th>Reference</th>
                    <th>Note</th>
                    <th>By</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($movements as $movement): ?>
                    <tr>
                        <td class="nowrap faint"><?= $movement->createdAt->format('Y-m-d H:i') ?></td>
                        <td>
                            <span class="badge <?= $movement->isIncoming() ? 'badge-success' : 'badge-info' ?>">
                                <i class="fa-solid <?= $movement->isIncoming() ? 'fa-arrow-down' : 'fa-arrow-up' ?>"
                                   aria-hidden="true"></i>
                                <?= Html::encode($movement->type->label()) ?>
                            </span>
                        </td>
                        <td class="table-numeric">
                            <span class="list-value <?= $movement->quantityChange >= 0 ? 'list-value-up' : 'list-value-down' ?>">
                                <?= $movement->quantityChange > 0 ? '+' : '' ?><?= $movement->quantityChange ?>
                            </span>
                        </td>
                        <td><?= Html::encode($movement->reference ?? '') ?></td>
                        <td class="faint"><?= Html::encode($movement->note ?? '') ?></td>
                        <td class="faint"><?= Html::encode($movement->createdByName) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <?php if ($canOperate): ?>
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                        Record movement
                    </h2>
                    <p class="panel-subtitle">Book stock in, out or correct the level.</p>
                </div>
            </div>

            <form method="post" action="<?= $urlGenerator->generate('stock-movement-create') ?>" class="stack">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="variantId" value="<?= $variant->id ?>">

                <div class="field">
                    <label class="field-label" for="type">Type</label>
                    <select id="type" name="type">
                        <?php foreach (MovementType::cases() as $type): ?>
                            <option value="<?= $type->value ?>"><?= Html::encode($type->label()) ?></option>
                        <?php endforeach ?>
                    </select>
                    <span class="field-hint">
                        "Stock in" and "Stock out" take a positive quantity. "Adjustment" takes a signed change.
                    </span>
                </div>

                <div class="field">
                    <label class="field-label" for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" step="1" required>
                </div>

                <div class="field">
                    <label class="field-label" for="reference">Reference</label>
                    <input type="text" id="reference" name="reference" placeholder="Invoice, delivery note…">
                </div>

                <div class="field">
                    <label class="field-label" for="unitCost">Unit cost</label>
                    <input type="text" id="unitCost" name="unitCost" inputmode="decimal" placeholder="Optional">
                </div>

                <div class="field">
                    <label class="field-label" for="note">Note</label>
                    <textarea id="note" name="note" rows="2"></textarea>
                </div>

                <button type="submit" class="button">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Record movement
                </button>
            </form>
        </div>
    <?php endif ?>
</div>
