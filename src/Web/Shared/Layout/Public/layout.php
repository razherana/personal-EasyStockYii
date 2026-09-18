<?php

declare(strict_types=1);

use App\Web\Shared\Layout\Public\PublicAsset;
use Yiisoft\Html\Html;

/**
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var Yiisoft\View\WebView $this
 */

$assetManager->register(PublicAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= Html::encode($this->getTitle()) ?> · <?= Html::encode($applicationParams->name) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="public-page">
    <?= $content ?>

    <p class="public-footer"><?= Html::encode($applicationParams->name) ?></p>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
