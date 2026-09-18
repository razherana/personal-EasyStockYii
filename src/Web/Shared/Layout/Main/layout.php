<?php

declare(strict_types=1);

use App\Web\Shared\Layout\Main\MainAsset;
use Yiisoft\Html\Html;

/**
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var \App\User\CurrentUserProvider $currentUser
 * @var \App\Web\Shared\Flash\FlashMessages $flashMessages
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$assetManager->register(MainAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$flashes = $flashMessages->pull();
$user = $currentUser->user();
$currentRouteName = $currentRoute->getName() ?? '';

/**
 * Highlights a navigation item when the current route belongs to that section.
 *
 * @param string $routePrefix
 */
$isActive = static fn(string $routePrefix): bool => $routePrefix !== ''
    && str_starts_with($currentRouteName, $routePrefix);

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body class="app">
<?php $this->beginBody() ?>

<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-brand-mark">ES</span>
            <span class="sidebar-brand-name"><?= Html::encode($applicationParams->name) ?></span>
        </div>

        <?php if ($user !== null): ?>
            <nav class="sidebar-nav" aria-label="Main navigation">
                <ul class="sidebar-nav-list">
                    <li>
                        <a class="sidebar-link<?= $isActive('dashboard') ? ' is-active' : '' ?>"
                           href="<?= $urlGenerator->generate('home') ?>"
                           <?= $isActive('dashboard') ? 'aria-current="page"' : '' ?>>Dashboard</a>
                    </li>
                    <?php if ($currentUser->can(App\Access\Permission::ProductView)): ?>
                        <li>
                            <a class="sidebar-link<?= $isActive('product') ? ' is-active' : '' ?>"
                               href="<?= $urlGenerator->generate('product-list') ?>"
                               <?= $isActive('product') ? 'aria-current="page"' : '' ?>>Products</a>
                        </li>
                    <?php endif ?>
                    <?php if ($currentUser->can(App\Access\Permission::OptionManage)): ?>
                        <li>
                            <a class="sidebar-link<?= $isActive('option') ? ' is-active' : '' ?>"
                               href="<?= $urlGenerator->generate('option-list') ?>"
                               <?= $isActive('option') ? 'aria-current="page"' : '' ?>>Option types</a>
                        </li>
                    <?php endif ?>
                    <?php if ($currentUser->can(App\Access\Permission::StockView)): ?>
                        <li>
                            <a class="sidebar-link<?= $isActive('stock') ? ' is-active' : '' ?>"
                               href="<?= $urlGenerator->generate('stock-list') ?>"
                               <?= $isActive('stock') ? 'aria-current="page"' : '' ?>>Stock</a>
                        </li>
                    <?php endif ?>
                    <?php if ($currentUser->can(App\Access\Permission::Export)): ?>
                        <li>
                            <a class="sidebar-link<?= $isActive('export') ? ' is-active' : '' ?>"
                               href="<?= $urlGenerator->generate('report-export') ?>"
                               <?= $isActive('export') ? 'aria-current="page"' : '' ?>>Export</a>
                        </li>
                    <?php endif ?>
                    <?php if ($currentUser->can(App\Access\Permission::Import)): ?>
                        <li>
                            <a class="sidebar-link<?= $isActive('import') ? ' is-active' : '' ?>"
                               href="<?= $urlGenerator->generate('report-import') ?>"
                               <?= $isActive('import') ? 'aria-current="page"' : '' ?>>Import</a>
                        </li>
                    <?php endif ?>
                    <?php if ($currentUser->can(App\Access\Permission::UserManage)): ?>
                        <li>
                            <a class="sidebar-link<?= $isActive('user') ? ' is-active' : '' ?>"
                               href="<?= $urlGenerator->generate('user-list') ?>"
                               <?= $isActive('user') ? 'aria-current="page"' : '' ?>>Users</a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        <?php endif ?>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <div class="topbar-title"><?= Html::encode($this->getTitle()) ?></div>
            <div class="topbar-user">
                <?php if ($user !== null): ?>
                    <span class="topbar-user-name"><?= Html::encode($user->name()) ?></span>
                    <span class="badge"><?= Html::encode($user->role->label()) ?></span>
                    <form class="topbar-logout" method="post" action="<?= $urlGenerator->generate('logout') ?>">
                        <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">
                        <button type="submit" class="button button-quiet">Log out</button>
                    </form>
                <?php endif ?>
            </div>
        </header>

        <main class="content">
            <?php foreach ($flashes['success'] as $message): ?>
                <div class="alert alert-success" role="status"><?= Html::encode($message) ?></div>
            <?php endforeach ?>
            <?php foreach ($flashes['error'] as $message): ?>
                <div class="alert alert-error" role="alert"><?= Html::encode($message) ?></div>
            <?php endforeach ?>

            <?= $content ?>
        </main>

        <footer class="footer">
            <span>&copy; <?= date('Y') ?> <?= Html::encode($applicationParams->name) ?></span>
        </footer>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
