<?php

declare(strict_types=1);

use App\Import\ImportResult;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ImportResult $result
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Import');

$csrf = Html::encode($csrf ?? '');
?>

<h1>Import</h1>

<div class="split">
    <div class="panel">
        <h2 class="panel-title">Upload a file</h2>
        <p class="muted">
            CSV and XLSX files are supported. The first line must contain the column names.
            Products are matched by SKU: existing products are updated, new ones are created.
        </p>

        <form method="post" action="<?= $urlGenerator->generate('report-import-submit') ?>"
              enctype="multipart/form-data" class="stack">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="field">
                <label class="field-label" for="file">CSV or XLSX file</label>
                <input type="file" id="file" name="file" accept=".csv,.xlsx,text/csv" required>
            </div>
            <button type="submit" class="button">Import</button>
        </form>
    </div>

    <div class="panel">
        <h2 class="panel-title">Columns</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Column</th>
                <th>Meaning</th>
            </tr>
            </thead>
            <tbody>
            <tr><td class="mono">sku</td><td>Required. Matches existing products.</td></tr>
            <tr><td class="mono">name</td><td>Required on creation, updated when present.</td></tr>
            <tr><td class="mono">unit</td><td>Defaults to <span class="mono">piece</span>.</td></tr>
            <tr><td class="mono">low_stock_threshold</td><td>Optional whole number.</td></tr>
            <tr><td class="mono">active</td><td><span class="mono">1</span> or <span class="mono">0</span>, defaults to active.</td></tr>
            <tr><td class="mono">options</td><td>Example: <span class="mono">Size=XL; Color=Red</span>. Missing types and values are created.</td></tr>
            <tr><td class="mono">quantity</td><td>Opening stock. Applied only when the product has exactly one variant.</td></tr>
            </tbody>
        </table>

        <p>
            <a class="button button-quiet"
               href="<?= $urlGenerator->generate('report-import-template', ['format' => 'csv']) ?>">Template CSV</a>
            <a class="button button-quiet"
               href="<?= $urlGenerator->generate('report-import-template', ['format' => 'xlsx']) ?>">Template XLSX</a>
        </p>
    </div>
</div>

<?php if ($result->rows > 0 || $result->hasErrors()): ?>
    <div class="panel">
        <h2 class="panel-title">Report</h2>
        <div class="alert <?= $result->hasErrors() ? 'alert-error' : 'alert-success' ?>" role="status">
            <?= Html::encode($result->summary()) ?>
        </div>

        <?php if ($result->hasErrors()): ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($result->errors as $error): ?>
                    <tr><td><?= Html::encode($error) ?></td></tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
<?php endif ?>
