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

<div class="page-header">
    <div>
        <h1 class="page-title">Import</h1>
        <p class="page-subtitle">
            Upload a CSV or XLSX file. Products are matched by SKU: existing products are updated,
            new ones are created.
        </p>
    </div>
</div>

<div class="split">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                    Upload a file
                </h2>
                <p class="panel-subtitle">
                    The first line must contain the column names.
                </p>
            </div>
        </div>

        <form method="post" action="<?= $urlGenerator->generate('report-import-submit') ?>"
              enctype="multipart/form-data" class="stack">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="field">
                <label class="field-label" for="file">CSV or XLSX file</label>
                <input type="file" id="file" name="file" accept=".csv,.xlsx,text/csv" required>
                <span class="field-hint">Products are matched by SKU, and missing option types are created.</span>
            </div>
            <button type="submit" class="button">
                <i class="fa-solid fa-upload" aria-hidden="true"></i>
                Import
            </button>
        </form>

        <p class="panel-note">
            Not sure about the format?
            <a class="button-link"
               href="<?= $urlGenerator->generate('report-import-template', ['format' => 'csv']) ?>">Template CSV</a>
            ·
            <a class="button-link"
               href="<?= $urlGenerator->generate('report-import-template', ['format' => 'xlsx']) ?>">Template XLSX</a>
        </p>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
                    Columns
                </h2>
                <p class="panel-subtitle">Accepted columns and what they mean.</p>
            </div>
        </div>

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
    </div>
</div>

<?php if ($result->rows > 0 || $result->hasErrors()): ?>
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                    Report
                </h2>
            </div>
        </div>

        <div class="alert <?= $result->hasErrors() ? 'alert-error' : 'alert-success' ?>" role="status">
            <i class="fa-solid <?= $result->hasErrors() ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"
               aria-hidden="true"></i>
            <span><?= Html::encode($result->summary()) ?></span>
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
