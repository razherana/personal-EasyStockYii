<?php

declare(strict_types=1);

use App\Web\Shared\Http\Pagination;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var Pagination $pagination
 * @var string $route Route name used for the page links.
 * @var array<string, int|string> $params Extra query parameters kept in the page links.
 * @var UrlGeneratorInterface $urlGenerator
 */

if ($pagination->pageCount() <= 1) {
    return;
}

$link = static fn(int $page): string => $urlGenerator->generate($route, [...$params, 'page' => $page]);
?>

<nav aria-label="Pagination">
    <ul class="pagination">
        <li>
            <?php if ($pagination->hasPrevious()): ?>
                <a href="<?= $link($pagination->previousPage()) ?>">Previous</a>
            <?php else: ?>
                <span class="faint">Previous</span>
            <?php endif ?>
        </li>
        <li><span class="is-current"><?= $pagination->page ?> / <?= $pagination->pageCount() ?></span></li>
        <li>
            <?php if ($pagination->hasNext()): ?>
                <a href="<?= $link($pagination->nextPage()) ?>">Next</a>
            <?php else: ?>
                <span class="faint">Next</span>
            <?php endif ?>
        </li>
    </ul>
</nav>
