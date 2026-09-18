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
?>

<div class="spread">
    <h1>Products</h1>
    <?php if ($canManage): ?>
        <a class="button" href="<?= $urlGenerator->generate('product-create') ?>">New product</a>
    <?php endif ?>
</div>

<form class="filters" method="get" action="<?= $urlGenerator->generate('product-list') ?>">
    <div class="field">
        <label class="field-label" for="search">Search</label>
        <input type="search" id="search" name="search" value="<?= Html::encode($search) ?>" placeholder="Name or SKU">
    </div>
    <button type="submit" class="button button-quiet">Search</button>
    <?php if ($search !== ''): ?>
        <a class="button-link" href="<?= $urlGenerator->generate('product-list') ?>">Clear</a>
    <?php endif ?>
</form>

<?php if ($items === []): ?>
    <p class="empty-state">No products found.</p>
<?php else: ?>
    <table class="table">
        <thead>
        <tr>
            <th>SKU</th>
            <th>Name</th>
            <th class="table-numeric">Variants</th>
            <th class="table-numeric">On hand</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td class="mono"><?= Html::encode($item->product->sku) ?></td>
                <td>
                    <a href="<?= $urlGenerator->generate('product-view', ['id' => $item->product->id]) ?>">
                        <?= Html::encode($item->product->name) ?>
                    </a>
                </td>
                <td class="table-numeric"><?= $item->variantCount ?></td>
                <td class="table-numeric"><?= $item->onHand ?></td>
                <td>
                    <?php if ($item->isLowStock()): ?>
                        <span class="badge">Low stock</span>
                    <?php else: ?>
                        <span class="faint">—</span>
                    <?php endif ?>
                </td>
                <td class="table-actions">
                    <?php if ($canManage): ?>
                        <a class="button-link"
                           href="<?= $urlGenerator->generate('product-edit', ['id' => $item->product->id]) ?>">Edit</a>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <?= $this->render(__DIR__ . '/../Shared/Partials/pagination', [
        'pagination' => $pagination,
        'route' => 'product-list',
        'params' => $search === '' ? [] : ['search' => $search],
    ]) ?>
<?php endif ?>
