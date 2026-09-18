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
?>

<p class="muted">
    <a href="<?= $urlGenerator->generate('product-view', ['id' => $product->id]) ?>">
        <?= Html::encode($product->name) ?>
    </a>
    / <span class="mono"><?= Html::encode($variant->sku) ?></span>
</p>

<div class="product-header">
    <div>
        <h1><?= Html::encode($variant->sku) ?></h1>
        <p>
            <?php foreach ($variant->optionLabels as $label): ?>
                <span class="badge"><?= Html::encode($label) ?></span>
            <?php endforeach ?>
            <?php if ($variant->isDefault) : ?>
                <span class="faint">default variant</span>
            <?php endif ?>
            <?php if (!$variant->isActive): ?>
                <span class="badge">inactive</span>
            <?php endif ?>
        </p>
    </div>
    <div class="stat">
        <span class="stat-label">On hand</span>
        <span class="stat-value"><?= $onHand ?></span>
    </div>
</div>

<div class="split">
    <div class="panel">
        <h2 class="panel-title">Movement history</h2>

        <?php if ($movements === []): ?>
            <p class="empty-state">No movements recorded for this variant yet.</p>
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
                        <td class="nowrap"><?= $movement->createdAt->format('Y-m-d H:i') ?></td>
                        <td><?= Html::encode($movement->type->label()) ?></td>
                        <td class="table-numeric"><?= $movement->quantityChange > 0 ? '+' : '' ?><?= $movement->quantityChange ?></td>
                        <td><?= Html::encode($movement->reference ?? '') ?></td>
                        <td><?= Html::encode($movement->note ?? '') ?></td>
                        <td class="faint"><?= Html::encode($movement->createdByName) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <?php if ($canOperate): ?>
        <div class="panel">
            <h2 class="panel-title">Record movement</h2>

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
                    <input type="text" id="reference" name="reference" placeholder="Invoice, delivery note...">
                </div>

                <div class="field">
                    <label class="field-label" for="unitCost">Unit cost</label>
                    <input type="text" id="unitCost" name="unitCost" inputmode="decimal" placeholder="Optional">
                </div>

                <div class="field">
                    <label class="field-label" for="note">Note</label>
                    <textarea id="note" name="note" rows="2"></textarea>
                </div>

                <button type="submit" class="button">Record movement</button>
            </form>
        </div>
    <?php endif ?>
</div>
