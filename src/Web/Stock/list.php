<?php

declare(strict_types=1);

use App\Stock\StockLevel;
use App\User\CurrentUserProvider;
use App\Web\Shared\Http\Pagination;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<StockLevel> $levels
 * @var string $search
 * @var bool $onlyLowStock
 * @var bool $includeInactive
 * @var Pagination $pagination
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Stock');

$params = array_filter([
    'search' => $search === '' ? null : $search,
    'low' => $onlyLowStock ? '1' : null,
    'inactive' => $includeInactive ? '1' : null,
], static fn(?string $value): bool => $value !== null);
?>

<h1>Stock</h1>

<form class="filters" method="get" action="<?= $urlGenerator->generate('stock-list') ?>">
    <div class="field">
        <label class="field-label" for="search">Search</label>
        <input type="search" id="search" name="search" value="<?= Html::encode($search) ?>"
               placeholder="Product name, product SKU or variant SKU">
    </div>
    <div class="field field-checkbox">
        <input type="checkbox" id="low" name="low" value="1" <?= $onlyLowStock ? 'checked' : '' ?>>
        <label class="field-label" for="low">Only low stock</label>
    </div>
    <div class="field field-checkbox">
        <input type="checkbox" id="inactive" name="inactive" value="1" <?= $includeInactive ? 'checked' : '' ?>>
        <label class="field-label" for="inactive">Include inactive</label>
    </div>
    <button type="submit" class="button button-quiet">Filter</button>
    <?php if ($params !== []): ?>
        <a class="button-link" href="<?= $urlGenerator->generate('stock-list') ?>">Clear</a>
    <?php endif ?>
</form>

<?php if ($levels === []): ?>
    <p class="empty-state">No variants match these filters.</p>
<?php else: ?>
    <table class="table">
        <thead>
        <tr>
            <th>Product</th>
            <th>Variant</th>
            <th class="table-numeric">On hand</th>
            <th>Status</th>
            <th>Last movement</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($levels as $level): ?>
            <tr>
                <td>
                    <a href="<?= $urlGenerator->generate('product-view', ['id' => $level->productId]) ?>">
                        <?= Html::encode($level->productName) ?>
                    </a>
                    <br><span class="faint mono"><?= Html::encode($level->productSku) ?></span>
                </td>
                <td class="mono"><?= Html::encode($level->variantSku) ?></td>
                <td class="table-numeric"><?= $level->onHand ?> <?= Html::encode($level->productUnit) ?></td>
                <td>
                    <?php if ($level->onHand <= 0): ?>
                        <span class="badge">Out of stock</span>
                    <?php elseif ($level->isLowStock()): ?>
                        <span class="badge">Low stock</span>
                    <?php elseif (!$level->isActiveVariant || !$level->isActiveProduct): ?>
                        <span class="badge">Inactive</span>
                    <?php else: ?>
                        <span class="faint">—</span>
                    <?php endif ?>
                </td>
                <td class="faint">
                    <?= $level->lastMovementAt === null ? '—' : $level->lastMovementAt->format('Y-m-d') ?>
                </td>
                <td class="table-actions">
                    <a class="button-link"
                       href="<?= $urlGenerator->generate('stock-variant', ['id' => $level->variantId]) ?>">History</a>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <?= $this->render(__DIR__ . '/../Shared/Partials/pagination', [
        'pagination' => $pagination,
        'route' => 'stock-list',
        'params' => $params,
    ]) ?>
<?php endif ?>
