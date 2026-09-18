<?php

declare(strict_types=1);

use App\Products\OptionType;
use App\Products\OptionValue;
use App\Products\Product;
use App\Web\Products\ProductInput;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ProductInput $input
 * @var list<OptionType> $types
 * @var array<int, list<OptionValue>> $values
 * @var list<int> $selected
 * @var list<string> $errors
 * @var bool $isEdit
 * @var Product|null $product
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$product ??= null;

$this->setTitle($isEdit ? 'Edit product' : 'New product');

$action = $isEdit && $product !== null
    ? $urlGenerator->generate('product-edit-submit', ['id' => $product->id])
    : $urlGenerator->generate('product-create-submit');
$cancelUrl = $isEdit && $product !== null
    ? $urlGenerator->generate('product-view', ['id' => $product->id])
    : $urlGenerator->generate('product-list');
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit product' : 'New product' ?></h1>
        <p class="page-subtitle">
            Option values combine into variants; each variant keeps its own stock ledger.
        </p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <span><?= Html::encode($error) ?></span>
    </div>
<?php endforeach ?>

<form method="post" action="<?= $action ?>">
    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-cube" aria-hidden="true"></i>
                    Details
                </h2>
                <p class="panel-subtitle">How the product is identified in the catalogue.</p>
            </div>
        </div>

        <div class="form-grid">
            <div class="field">
                <label class="field-label" for="sku">SKU</label>
                <input type="text" id="sku" name="sku" value="<?= Html::encode($input->sku) ?>" required>
            </div>
            <div class="field">
                <label class="field-label" for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= Html::encode($input->name) ?>" required>
            </div>
            <div class="field">
                <label class="field-label" for="unit">Unit</label>
                <input type="text" id="unit" name="unit" value="<?= Html::encode($input->unit) ?>"
                       placeholder="piece, foot, meter…">
            </div>
            <div class="field">
                <label class="field-label" for="lowStockThreshold">Low stock threshold</label>
                <input type="number" id="lowStockThreshold" name="lowStockThreshold" min="0" step="1"
                       value="<?= Html::encode($input->lowStockThreshold) ?>">
                <span class="field-hint">Warn when the stock level of this product reaches this value.</span>
            </div>
            <div class="field field-wide">
                <label class="field-label" for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= Html::encode($input->description) ?></textarea>
            </div>
            <div class="field field-checkbox">
                <input type="checkbox" id="isActive" name="isActive" value="1"
                       <?= $input->isActiveChecked() ? 'checked' : '' ?>>
                <label class="field-label" for="isActive">Active</label>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    Options
                </h2>
                <p class="panel-subtitle">Each selected value becomes part of the variant combinations.</p>
            </div>
        </div>

        <?php if ($types === []): ?>
            <p class="empty-state">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                <span>
                    No option types yet.
                    <a href="<?= $urlGenerator->generate('option-list') ?>">Create option types</a>
                    to sell the same product in several sizes, colours, etc.
                </span>
            </p>
        <?php else: ?>
            <div class="form-grid">
                <?php foreach ($types as $type): ?>
                    <div class="field">
                        <span class="field-label"><?= Html::encode($type->name) ?></span>
                        <?php foreach ($values[$type->id] ?? [] as $optionValue): ?>
                            <label class="field-checkbox">
                                <input type="checkbox" name="optionValueIds[]"
                                       value="<?= $optionValue->id ?>"
                                       <?= in_array($optionValue->id, $selected, true) ? 'checked' : '' ?>>
                                <span><?= Html::encode($optionValue->value) ?></span>
                            </label>
                        <?php endforeach ?>
                        <?php if (($values[$type->id] ?? []) === []): ?>
                            <span class="field-hint">No values for this type yet.</span>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="button">
            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
            <?= $isEdit ? 'Save changes' : 'Create product' ?>
        </button>
        <a class="button button-quiet" href="<?= $cancelUrl ?>">Cancel</a>
    </div>
</form>
