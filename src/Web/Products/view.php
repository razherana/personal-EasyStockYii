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
?>

<div class="product-header">
    <div>
        <h1><?= Html::encode($product->name) ?></h1>
        <p class="muted">
            SKU <span class="mono"><?= Html::encode($product->sku) ?></span>
            · <?= Html::encode($product->unit) ?>
            <?php if ($product->lowStockThreshold !== null): ?>
                · low stock threshold <?= $product->lowStockThreshold ?>
            <?php endif ?>
            <?php if (!$product->isActive): ?>
                · <span class="badge">Inactive</span>
            <?php endif ?>
        </p>
    </div>

    <div class="row">
        <?php if ($canManage): ?>
            <a class="button button-quiet"
               href="<?= $urlGenerator->generate('product-edit', ['id' => $product->id]) ?>">Edit</a>
        <?php endif ?>
        <?php if ($canManage && $product->isActive): ?>
            <form method="post" action="<?= $urlGenerator->generate('product-delete', ['id' => $product->id]) ?>"
                  onsubmit="return confirm('Deactivate this product?');">
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">
                <button type="submit" class="button button-danger">Deactivate</button>
            </form>
        <?php endif ?>
    </div>
</div>

<?php if ($product->description !== null): ?>
    <div class="panel">
        <p><?= nl2br(Html::encode($product->description)) ?></p>
    </div>
<?php endif ?>

<div class="split">
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Variants and stock</h2>
                <?php if ($canOperate): ?>
                    <a class="button-link"
                       href="<?= $urlGenerator->generate('stock-list', ['search' => $product->sku]) ?>">All movements</a>
                <?php endif ?>
            </div>

            <?php if ($levels === []): ?>
                <p class="empty-state">This product has no variants yet.</p>
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
                            <td class="mono"><?= Html::encode($level->variantSku) ?></td>
                            <td>
                                <?php foreach ($variantsById[$level->variantId]->optionLabels ?? [] as $label): ?>
                                    <span class="badge"><?= Html::encode($label) ?></span>
                                <?php endforeach ?>
                                <?php if ($level->isDefaultVariant): ?>
                                    <span class="faint">default</span>
                                <?php endif ?>
                                <?php if (!$level->isActiveVariant): ?>
                                    <span class="badge">inactive</span>
                                <?php endif ?>
                            </td>
                            <td class="table-numeric"><?= $level->onHand ?></td>
                            <td class="table-actions">
                                <a class="button-link"
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
            <img src="<?= $urlGenerator->generate('public-product-qr', ['token' => $product->qrToken]) ?>"
                 alt="QR code for <?= Html::encode($product->name) ?>" width="200" height="200">
            <p class="field-hint">Print this code on the shelf label.</p>
            <p><a href="<?= Html::encode($publicUrl) ?>" target="_blank" rel="noopener">Open product page</a></p>
            <p><a class="button button-quiet"
                  href="<?= $urlGenerator->generate('public-product-qr', ['token' => $product->qrToken]) ?>"
                  download="qr-<?= Html::encode($product->sku) ?>.svg">Download SVG</a></p>
        </div>
    </div>
</div>
