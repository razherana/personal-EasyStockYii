<?php

declare(strict_types=1);

use App\Access\Permission;
use App\Products\ProductListItem;
use App\User\CurrentUserProvider;
use App\Web\Shared\Http\Pagination;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<ProductListItem> $items
 * @var string $search
 * @var Pagination $pagination
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Products');
$canManage = $currentUser->can(Permission::ProductManage);

/**
 * Stock health of a product row: status, label and how full the gauge is.
 *
 * The gauge shows how far the level is above the low stock threshold, so a variant at exactly
 * its threshold sits at half of the gauge.
 *
 * @return array{0: string, 1: string, 2: int}
 */
$health = static function (ProductListItem $item): array {
    $threshold = $item->product->lowStockThreshold;
    $full = static fn(float $ratio): int => (int) min(100, max(0, round($ratio * 50)));

    if ($item->onHand <= 0) {
        return ['danger', 'Out of stock', 0];
    }

    if ($item->isLowStock()) {
        return ['warning', 'Low stock', $threshold === null ? 10 : max(10, $full($item->onHand / $threshold))];
    }

    return ['success', 'Healthy', $threshold === null || $threshold <= 0 ? 75 : $full($item->onHand / $threshold)];
};

/**
 * Two letter tile label taken from the first two words of the product name.
 */
$tileLabel = static function (string $name): string {
    $words = preg_split('/[^\p{L}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $letters = '';

    foreach ($words as $word) {
        if (mb_strlen($letters) >= 2) {
            break;
        }

        if (preg_match('/^\d/', $word) === 1) {
            continue;
        }

        $letters .= mb_strtoupper(mb_substr($word, 0, 1));
    }

    if (mb_strlen($letters) < 2) {
        $letters = mb_strtoupper(mb_substr($words[0] ?? '', 0, 2));
    }

    return $letters === '' ? '?' : $letters;
};
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle">Everything in the catalogue with its variants and stock level.</p>
    </div>
    <?php if ($canManage): ?>
        <div class="page-actions">
            <a class="button" href="<?= $urlGenerator->generate('product-create') ?>">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                New product
            </a>
        </div>
    <?php endif ?>
</div>

<form class="filters" method="get" action="<?= $urlGenerator->generate('product-list') ?>">
    <div class="field field-grow">
        <label class="field-label visually-hidden" for="search">Search products</label>
        <span class="search-field">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="search" name="search" value="<?= Html::encode($search) ?>"
                   placeholder="Search by name or SKU">
        </span>
    </div>
    <div class="filters-actions">
        <?php if ($search !== ''): ?>
            <a class="button-link" href="<?= $urlGenerator->generate('product-list') ?>">Clear</a>
        <?php endif ?>
        <button type="submit" class="button button-quiet">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            Search
        </button>
    </div>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
        <span>No products found.</span>
    </p>
<?php else: ?>
    <div class="product-list">
        <?php foreach ($items as $item): ?>
            <?php [$tone, $healthLabel, $healthValue] = $health($item); ?>
            <article class="product-row">
                <span class="tile tile-<?= crc32($item->product->sku) % 6 + 1 ?>" aria-hidden="true">
                    <?= Html::encode($tileLabel($item->product->name)) ?>
                </span>

                <div class="product-main">
                    <h3 class="product-name">
                        <a href="<?= $urlGenerator->generate('product-view', ['id' => $item->product->id]) ?>">
                            <?= Html::encode($item->product->name) ?>
                        </a>
                    </h3>
                    <div class="product-meta">
                        <span class="mono"><?= Html::encode($item->product->sku) ?></span>
                        <span>·</span>
                        <span><?= Html::encode($item->product->unit) ?></span>
                        <?php if ($tone === 'warning'): ?>
                            <span class="badge badge-warning">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                Low stock
                            </span>
                        <?php elseif ($tone === 'danger'): ?>
                            <span class="badge badge-danger">
                                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                                Out of stock
                            </span>
                        <?php endif ?>
                        <?php if (!$item->product->isActive): ?>
                            <span class="badge">Inactive</span>
                        <?php endif ?>
                    </div>
                </div>

                <div class="product-stats">
                    <div class="product-stat">
                        <span class="product-stat-icon" aria-hidden="true">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>
                        <span class="product-stat-meta">
                            <span class="product-stat-label">Variants</span>
                            <span class="product-stat-value"><?= $item->variantCount ?></span>
                        </span>
                    </div>
                    <div class="product-stat">
                        <span class="product-stat-icon" aria-hidden="true">
                            <i class="fa-solid fa-warehouse"></i>
                        </span>
                        <span class="product-stat-meta">
                            <span class="product-stat-label">On hand</span>
                            <span class="product-stat-value"><?= $item->onHand ?> <?= Html::encode($item->product->unit) ?></span>
                        </span>
                    </div>
                </div>

                <div class="product-health">
                    <span class="gauge gauge-<?= $tone ?>"
                          style="--gauge-value: <?= $healthValue ?>"
                          role="img"
                          aria-label="Stock health: <?= Html::encode($healthLabel) ?>">
                        <span><?= $healthValue ?>%</span>
                    </span>
                    <span class="product-stat-meta">
                        <span class="product-stat-label">Stock health</span>
                        <span class="product-stat-value"><?= Html::encode($healthLabel) ?></span>
                    </span>
                </div>

                <div class="product-actions">
                    <a class="button button-quiet button-small"
                       href="<?= $urlGenerator->generate('product-view', ['id' => $item->product->id]) ?>">Open</a>
                    <?php if ($canManage): ?>
                        <a class="icon-button"
                           href="<?= $urlGenerator->generate('product-edit', ['id' => $item->product->id]) ?>"
                           title="Edit product">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                            <span class="visually-hidden">Edit <?= Html::encode($item->product->name) ?></span>
                        </a>
                    <?php endif ?>
                </div>
            </article>
        <?php endforeach ?>
    </div>

    <?= $this->render(__DIR__ . '/../Shared/Partials/pagination', [
        'pagination' => $pagination,
        'route' => 'product-list',
        'params' => $search === '' ? [] : ['search' => $search],
    ]) ?>
<?php endif ?>
