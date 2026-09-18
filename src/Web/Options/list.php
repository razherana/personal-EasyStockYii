<?php

declare(strict_types=1);

use App\Products\OptionType;
use App\Products\OptionValue;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<OptionType> $types
 * @var array<int, list<OptionValue>> $values
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Option types');

$csrf = Html::encode($csrf ?? '');
?>

<h1>Option types</h1>
<p class="muted">
    Option types describe how a product can vary, for example Size, Colour or Logo. Values are the
    concrete choices, for example XL, Red or "with logo". Products are split into variants for every
    combination of the values you select.
</p>

<div class="split">
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Types and values</h2>
            <span class="faint"><?= count($types) ?> type(s)</span>
        </div>

        <?php if ($types === []): ?>
            <p class="empty-state">No option types yet. Add the first one on the right.</p>
        <?php endif ?>

        <?php foreach ($types as $type): ?>
            <h3><?= Html::encode($type->name) ?></h3>
            <div class="row">
                <?php foreach ($values[$type->id] ?? [] as $optionValue): ?>
                    <span class="badge">
                        <?= Html::encode($optionValue->value) ?>
                        <form method="post"
                              action="<?= $urlGenerator->generate('option-value-delete', [
                                  'id' => $type->id,
                                  'valueId' => $optionValue->id,
                              ]) ?>"
                              class="nowrap">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <button type="submit" class="button-link" title="Delete value">×</button>
                        </form>
                    </span>
                <?php endforeach ?>
                <?php if (($values[$type->id] ?? []) === []): ?>
                    <span class="faint">no values yet</span>
                <?php endif ?>
            </div>

            <form class="filters" method="post"
                  action="<?= $urlGenerator->generate('option-value-create', ['id' => $type->id]) ?>">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <div class="field">
                    <label class="field-label visually-hidden" for="value-<?= $type->id ?>">New value</label>
                    <input type="text" id="value-<?= $type->id ?>" name="value" placeholder="New value" required>
                </div>
                <button type="submit" class="button button-quiet">Add value</button>
            </form>

            <form method="post" action="<?= $urlGenerator->generate('option-delete', ['id' => $type->id]) ?>"
                  onsubmit="return confirm('Delete this option type?');">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="button-link">Delete type</button>
            </form>

            <hr>
        <?php endforeach ?>
    </div>

    <div class="panel">
        <h2 class="panel-title">Add option type</h2>
        <form method="post" action="<?= $urlGenerator->generate('option-create') ?>" class="stack">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="field">
                <label class="field-label" for="type-name">Name</label>
                <input type="text" id="type-name" name="name" placeholder="Size, Colour, Logo..." required>
            </div>
            <button type="submit" class="button">Add type</button>
        </form>
    </div>
</div>
