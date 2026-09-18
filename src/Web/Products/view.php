<?php

declare(strict_types=1);

use App\Access\Permission;
use App\Products\Product;
use App\Products\ProductVariant;
use App\Stock\StockLevel;
use App\User\CurrentUserProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var Product $product
 * @var list<StockLevel> $levels
 * @var array<int, ProductVariant> $variantsById
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle($product->name);

$canManage = $currentUser->can(Permission::ProductManage);
$canOperate = $currentUser->can(Permission::StockOperate);
$publicUrl = $urlGenerator->generateAbsolute('public-product', ['token' => $product->qrToken]);
$totalOnHand = 0;

foreach ($levels as $level) {
    $totalOnHand += $level->onHand;
}
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= $urlGenerator->generate('product-list') ?>">Products</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span class="faint"><?= Html::encode($product->sku) ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= Html::encode($product->name) ?></h1>
        <div class="product-meta">
            <span class="badge badge-info mono"><?= Html::encode($product->sku) ?></span>
            <span><?= Html::encode($product->unit) ?></span>
            <?php if ($product->lowStockThreshold !== null): ?>
                <span class="badge">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    Threshold <?= $product->lowStockThreshold ?>
                </span>
            <?php endif ?>
            <?php if (!$product->isActive): ?>
                <span class="badge">Inactive</span>
            <?php endif ?>
        </div>
    </div>

    <div class="page-actions">
        <?php if ($canManage): ?>
            <a class="button button-quiet" href="<?= $urlGenerator->generate('product-edit', ['id' => $product->id]) ?>">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                Edit
            </a>
        <?php endif ?>
        <?php if ($canManage && $product->isActive): ?>
            <form method="post" action="<?= $urlGenerator->generate('product-delete', ['id' => $product->id]) ?>"
                  onsubmit="return confirm('Deactivate this product?');">
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">
                <button type="submit" class="button button-danger">
                    <i class="fa-solid fa-ban" aria-hidden="true"></i>
                    Deactivate
                </button>
            </form>
        <?php endif ?>
    </div>
</div>

<?php if ($product->description !== null): ?>
    <div class="panel">
        <p class="muted"><?= nl2br(Html::encode($product->description)) ?></p>
    </div>
<?php endif ?>

<div class="stat-grid">
    <div class="stat">
        <span class="stat-label">Variants</span>
        <span class="stat-value"><?= count($levels) ?></span>
    </div>
    <div class="stat">
        <span class="stat-label">Units on hand</span>
        <span class="stat-value"><?= $totalOnHand ?></span>
    </div>
</div>

<div class="split">
    <div>
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                        Variants and stock
                    </h2>
                    <p class="panel-subtitle">Every combination keeps its own ledger.</p>
                </div>
                <?php if ($canOperate): ?>
                    <a class="button-link"
                       href="<?= $urlGenerator->generate('stock-list', ['search' => $product->sku]) ?>">All movements</a>
                <?php endif ?>
            </div>

            <?php if ($levels === []): ?>
                <p class="empty-state">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                    <span>This product has no variants yet.</span>
                </p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Variant</th>
                        <th>Options</th>
                        <th class="table-numeric">On hand</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($levels as $level): ?>
                        <tr>
                            <td><span class="mono cell-title"><?= Html::encode($level->variantSku) ?></span></td>
                            <td>
                                <span class="product-meta">
                                    <?php foreach ($variantsById[$level->variantId]->optionLabels ?? [] as $label): ?>
                                        <span class="badge badge-info"><?= Html::encode($label) ?></span>
                                    <?php endforeach ?>
                                    <?php if ($level->isDefaultVariant): ?>
                                        <span class="faint">default</span>
                                    <?php endif ?>
                                    <?php if (!$level->isActiveVariant): ?>
                                        <span class="badge">inactive</span>
                                    <?php endif ?>
                                </span>
                            </td>
                            <td class="table-numeric">
                                <?php if ($level->onHand <= 0): ?>
                                    <span class="badge badge-danger"><?= $level->onHand ?></span>
                                <?php elseif ($level->isLowStock()): ?>
                                    <span class="badge badge-warning"><?= $level->onHand ?></span>
                                <?php else: ?>
                                    <span class="cell-title"><?= $level->onHand ?></span>
                                <?php endif ?>
                            </td>
                            <td class="table-actions">
                                <a class="button button-quiet button-small"
                                   href="<?= $urlGenerator->generate('stock-variant', ['id' => $level->variantId]) ?>">
                                    History
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </div>
    </div>

    <div>
        <div class="panel qr-card">
            <h2 class="panel-title">
                <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                Shelf label
            </h2>
            <img src="<?= $urlGenerator->generate('public-product-qr', ['token' => $product->qrToken]) ?>"
                 alt="QR code for <?= Html::encode($product->name) ?>" width="200" height="200">
            <p class="field-hint">Print this code on the shelf label.</p>
            <p><a href="<?= Html::encode($publicUrl) ?>" target="_blank" rel="noopener">Open product page</a></p>
            <p>
                <a class="button button-quiet button-small"
                   href="<?= $urlGenerator->generate('public-product-qr', ['token' => $product->qrToken]) ?>"
                   download="qr-<?= Html::encode($product->sku) ?>.svg">
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    Download SVG
                </a>
            </p>
        </div>
    </div>
</div>
