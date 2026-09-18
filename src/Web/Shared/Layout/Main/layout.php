<?php

declare(strict_types=1);

use App\Access\Permission;
use App\Web\Shared\Layout\Main\MainAsset;
use App\Web\Shared\Layout\Main\NavItem;
use App\Web\Shared\Layout\Main\NavSection;
use App\Web\Shared\Layout\Main\Navigation;
use Yiisoft\Html\Html;

/**
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var \App\User\CurrentUserProvider $currentUser
 * @var \App\Web\Shared\Flash\FlashMessages $flashMessages
 * @var \App\Web\Shared\Layout\Main\Navigation $navigation
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

$sections = $user === null ? [] : $navigation->sections();
$activeSection = $user === null ? null : $navigation->activeSection($currentRouteName);

$initials = static function (string $name): string {
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($words, 0, 2) as $word) {
        $letters .= mb_strtoupper(mb_substr($word, 0, 1));
    }

    return $letters === '' ? '?' : $letters;
};

$itemUrl = static fn(NavItem $item): string => $urlGenerator->generate($item->route, $item->params);
$itemIsActive = static fn(NavItem $item): bool => Navigation::isItemActive($item, $currentRouteName);
$sectionIsActive = static fn(NavSection $section): bool => $activeSection !== null
    && $activeSection->id === $section->id;

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <title><?= Html::encode($this->getTitle()) ?> · <?= Html::encode($applicationParams->name) ?></title>
    <?php $this->head() ?>
</head>
<body class="app">
<?php $this->beginBody() ?>

<div class="app-shell<?= $user === null ? ' is-guest' : '' ?>">
    <aside class="rail" aria-label="Sections">
        <a class="rail-brand" href="<?= $urlGenerator->generate('home') ?>">
            <span aria-hidden="true">ES</span>
            <span class="visually-hidden"><?= Html::encode($applicationParams->name) ?></span>
        </a>

        <nav class="rail-nav" aria-label="Sections">
            <?php foreach ($sections as $section): ?>
                <a class="rail-link<?= $sectionIsActive($section) ? ' is-active' : '' ?>"
                   href="<?= $urlGenerator->generate($section->route) ?>"
                   data-tooltip="<?= Html::encode($section->label) ?>"
                   <?= $sectionIsActive($section) ? 'aria-current="true"' : '' ?>>
                    <i class="fa-solid <?= Html::encode($section->icon) ?>" aria-hidden="true"></i>
                    <span class="visually-hidden"><?= Html::encode($section->label) ?></span>
                </a>
            <?php endforeach ?>
        </nav>

        <div class="rail-spacer"></div>

        <?php if ($currentUser->can(Permission::ProductManage)): ?>
            <a class="rail-action"
               href="<?= $urlGenerator->generate('product-create') ?>"
               data-tooltip="New product">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span class="visually-hidden">New product</span>
            </a>
        <?php endif ?>
    </aside>

    <aside class="sidebar" id="sidebar" aria-label="Section navigation">
        <div class="sidebar-head">
            <span class="sidebar-eyebrow"><?= Html::encode($applicationParams->name) ?></span>
            <span class="sidebar-title"><?= Html::encode($activeSection->label ?? 'Menu') ?></span>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($activeSection->groups ?? [] as $group): ?>
                <div class="nav-group">
                    <span class="nav-group-label"><?= Html::encode($group->label) ?></span>
                    <ul class="nav-list">
                        <?php foreach ($group->items as $item): ?>
                            <li>
                                <a class="nav-link<?= $itemIsActive($item) ? ' is-active' : '' ?>"
                                   href="<?= $itemUrl($item) ?>"
                                   <?= $itemIsActive($item) ? 'aria-current="page"' : '' ?>>
                                    <i class="fa-solid <?= Html::encode($item->icon) ?> nav-icon"
                                       aria-hidden="true"></i>
                                    <span><?= Html::encode($item->label) ?></span>
                                    <?php if ($item->badge !== null): ?>
                                        <span class="nav-count"><?= $item->badge ?></span>
                                    <?php endif ?>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endforeach ?>
        </nav>

        <?php if ($user !== null): ?>
            <div class="sidebar-foot">
                <div class="profile">
                    <span class="avatar" aria-hidden="true"><?= Html::encode($initials($user->name())) ?></span>
                    <span class="profile-meta">
                        <span class="profile-name" title="<?= Html::encode($user->name()) ?>">
                            <?= Html::encode($user->name()) ?>
                        </span>
                        <span class="profile-role"><?= Html::encode($user->role->label()) ?></span>
                    </span>
                    <form class="profile-logout" method="post" action="<?= $urlGenerator->generate('logout') ?>">
                        <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">
                        <button type="submit" class="icon-button icon-button-quiet" title="Log out">
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            <span class="visually-hidden">Log out</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif ?>
    </aside>

    <div class="sidebar-backdrop" data-nav-close></div>

    <div class="app-main">
        <header class="topbar">
            <button type="button"
                    class="icon-button topbar-menu"
                    data-nav-toggle
                    aria-controls="sidebar"
                    aria-expanded="false">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                <span class="visually-hidden">Toggle navigation</span>
            </button>

            <div class="topbar-title">
                <span class="topbar-eyebrow"><?= Html::encode($activeSection->label ?? '') ?></span>
                <span class="topbar-heading"><?= Html::encode($this->getTitle()) ?></span>
            </div>

            <?php if ($currentUser->can(Permission::ProductView)): ?>
                <form class="topbar-search search-field" method="get"
                      action="<?= $urlGenerator->generate('product-list') ?>" role="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <label class="visually-hidden" for="topbar-search">Search products</label>
                    <input type="search" id="topbar-search" name="search" placeholder="Search products, SKU…">
                </form>
            <?php endif ?>

            <div class="topbar-actions">
                <?php if ($currentUser->can(Permission::ProductManage)): ?>
                    <a class="button button-small" href="<?= $urlGenerator->generate('product-create') ?>">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        New product
                    </a>
                <?php endif ?>
            </div>
        </header>

        <main class="content">
            <?php foreach ($flashes['success'] as $message): ?>
                <div class="alert alert-success" role="status" data-flash-dismiss>
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span><?= Html::encode($message) ?></span>
                </div>
            <?php endforeach ?>
            <?php foreach ($flashes['error'] as $message): ?>
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span><?= Html::encode($message) ?></span>
                </div>
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
