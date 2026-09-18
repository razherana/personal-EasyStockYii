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
    <span class="public-brand">Product summary</span>
    <h1 class="public-title"><?= Html::encode($product->name) ?></h1>
    <p class="muted">
        SKU <span class="mono"><?= Html::encode($product->sku) ?></span>
        · <?= $total ?> <?= Html::encode($product->unit) ?> in stock
    </p>
</header>

<?php if ($product->description !== null): ?>
    <p><?= nl2br(Html::encode($product->description)) ?></p>
<?php endif ?>

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
            <td class="mono"><?= Html::encode($level->variantSku) ?></td>
            <td class="table-numeric"><?= $level->onHand ?></td>
            <td>
                <?php if ($level->onHand <= 0): ?>
                    <span class="badge">Out of stock</span>
                <?php elseif ($level->isLowStock()): ?>
                    <span class="badge">Low stock</span>
                <?php else: ?>
                    <span class="faint">Available</span>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<div class="qr-card">
    <img src="<?= $urlGenerator->generate('public-product-qr', ['token' => $product->qrToken]) ?>"
         alt="QR code for <?= Html::encode($product->name) ?>" width="200" height="200">
</div>
