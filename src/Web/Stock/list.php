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

<div class="page-header">
    <div>
        <h1 class="page-title">Stock</h1>
        <p class="page-subtitle">Levels per variant, computed from the movement ledger.</p>
    </div>
</div>

<form class="filters" method="get" action="<?= $urlGenerator->generate('stock-list') ?>">
    <div class="field field-grow">
        <label class="field-label visually-hidden" for="search">Search stock</label>
        <span class="search-field">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="search" name="search" value="<?= Html::encode($search) ?>"
                   placeholder="Product name, product SKU or variant SKU">
        </span>
    </div>
    <div class="field field-checkbox">
        <input type="checkbox" id="low" name="low" value="1" <?= $onlyLowStock ? 'checked' : '' ?>>
        <label class="field-label" for="low">Only low stock</label>
    </div>
    <div class="field field-checkbox">
        <input type="checkbox" id="inactive" name="inactive" value="1" <?= $includeInactive ? 'checked' : '' ?>>
        <label class="field-label" for="inactive">Include inactive</label>
    </div>
    <div class="filters-actions">
        <?php if ($params !== []): ?>
            <a class="button-link" href="<?= $urlGenerator->generate('stock-list') ?>">Clear</a>
        <?php endif ?>
        <button type="submit" class="button button-quiet">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            Filter
        </button>
    </div>
</form>

<?php if ($levels === []): ?>
    <p class="empty-state">
        <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
        <span>No variants match these filters.</span>
    </p>
<?php else: ?>
    <div class="panel panel-flush">
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
                        <a class="cell-title"
                           href="<?= $urlGenerator->generate('product-view', ['id' => $level->productId]) ?>">
                            <?= Html::encode($level->productName) ?>
                        </a>
                        <span class="cell-sub mono"><?= Html::encode($level->productSku) ?></span>
                    </td>
                    <td><span class="mono"><?= Html::encode($level->variantSku) ?></span></td>
                    <td class="table-numeric">
                        <span class="cell-title">
                            <?= $level->onHand ?> <?= Html::encode($level->productUnit) ?>
                        </span>
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
                        <?php elseif (!$level->isActiveVariant || !$level->isActiveProduct): ?>
                            <span class="badge">Inactive</span>
                        <?php else: ?>
                            <span class="badge badge-success">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                Healthy
                            </span>
                        <?php endif ?>
                    </td>
                    <td class="faint">
                        <?= $level->lastMovementAt === null ? '—' : $level->lastMovementAt->format('Y-m-d') ?>
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
    </div>

    <?= $this->render(__DIR__ . '/../Shared/Partials/pagination', [
        'pagination' => $pagination,
        'route' => 'stock-list',
        'params' => $params,
    ]) ?>
<?php endif ?>
