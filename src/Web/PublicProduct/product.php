<?php

declare(strict_types=1);

use App\Products\Product;
use App\Stock\StockLevel;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var Product $product
 * @var list<StockLevel> $levels
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle($product->name);

$total = 0;

foreach ($levels as $level) {
    $total += $level->onHand;
}
?>

<header class="public-header">
    <span class="public-brand">
        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
        Product summary
    </span>
    <h1 class="public-title"><?= Html::encode($product->name) ?></h1>
    <p class="public-meta">
        <span class="badge badge-info mono"><?= Html::encode($product->sku) ?></span>
        <span><?= $total ?> <?= Html::encode($product->unit) ?> in stock</span>
        <?php if (!$product->isActive): ?>
            <span class="badge">Inactive</span>
        <?php endif ?>
    </p>
</header>

<?php if ($product->description !== null): ?>
    <div class="panel">
        <p class="muted"><?= nl2br(Html::encode($product->description)) ?></p>
    </div>
<?php endif ?>

<div class="panel panel-flush">
    <table class="table">
        <thead>
        <tr>
            <th>Variant</th>
            <th class="table-numeric">In stock</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($levels as $level): ?>
            <tr>
                <td><span class="mono cell-title"><?= Html::encode($level->variantSku) ?></span></td>
                <td class="table-numeric">
                    <span class="cell-title"><?= $level->onHand ?></span>
                </td>
                <td>
                    <?php if ($level->onHand <= 0): ?>
                        <span class="badge badge-danger">
                            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                            Out of stock
                        </span>
                    <?php elseif ($level->isLowStock()): ?>
                        <span class="badge badge-warning">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                            Low stock
                        </span>
                    <?php else: ?>
                        <span class="badge badge-success">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Available
                        </span>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>

<div class="panel qr-card">
    <img src="<?= $urlGenerator->generate('public-product-qr', ['token' => $product->qrToken]) ?>"
         alt="QR code for <?= Html::encode($product->name) ?>" width="200" height="200">
    <p class="field-hint">Scan the shelf label to open this page.</p>
</div>
