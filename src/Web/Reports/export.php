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

$icons = [
    ExportFormat::Csv->value => 'fa-file-csv',
    ExportFormat::Xlsx->value => 'fa-file-excel',
    ExportFormat::Pdf->value => 'fa-file-pdf',
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Export</h1>
        <p class="page-subtitle">Every export is generated from the current data and downloads immediately.</p>
    </div>
</div>

<div class="card-grid">
    <?php foreach ($datasets as $dataset): ?>
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        <i class="fa-solid fa-database" aria-hidden="true"></i>
                        <?= Html::encode($dataset->label()) ?>
                    </h2>
                    <p class="panel-subtitle"><?= Html::encode($dataset->description()) ?></p>
                </div>
            </div>

            <div class="page-actions">
                <?php foreach ($formats as $format): ?>
                    <a class="button button-quiet"
                       href="<?= $urlGenerator->generate('report-export-download', [
                           'format' => $format->value,
                           'dataset' => $dataset->value,
                       ]) ?>">
                        <i class="fa-solid <?= $icons[$format->value] ?? 'fa-file-arrow-down' ?>" aria-hidden="true"></i>
                        <?= Html::encode($format->label()) ?>
                    </a>
                <?php endforeach ?>
            </div>
        </div>
    <?php endforeach ?>
</div>
