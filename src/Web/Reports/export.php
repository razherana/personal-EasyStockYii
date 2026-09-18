<?php

declare(strict_types=1);

use App\Export\ExportDatasetName;
use App\Export\ExportFormat;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<ExportDatasetName> $datasets
 * @var list<ExportFormat> $formats
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Export');
?>

<h1>Export</h1>
<p class="muted">Every export is generated from the current data and downloads immediately.</p>

<table class="table">
    <thead>
    <tr>
        <th>Data set</th>
        <th>Contents</th>
        <?php foreach ($formats as $format): ?>
            <th><?= Html::encode($format->label()) ?></th>
        <?php endforeach ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($datasets as $dataset): ?>
        <tr>
            <td><?= Html::encode($dataset->label()) ?></td>
            <td class="muted"><?= Html::encode($dataset->description()) ?></td>
            <?php foreach ($formats as $format): ?>
                <td>
                    <a class="button-link"
                       href="<?= $urlGenerator->generate('report-export-download', [
                           'format' => $format->value,
                           'dataset' => $dataset->value,
                       ]) ?>">Download</a>
                </td>
            <?php endforeach ?>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
