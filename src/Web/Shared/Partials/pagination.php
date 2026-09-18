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
                <a href="<?= $link($pagination->previousPage()) ?>">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    Previous
                </a>
            <?php else: ?>
                <span class="faint">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    Previous
                </span>
            <?php endif ?>
        </li>
        <li><span class="is-current">Page <?= $pagination->page ?> of <?= $pagination->pageCount() ?></span></li>
        <li>
            <?php if ($pagination->hasNext()): ?>
                <a href="<?= $link($pagination->nextPage()) ?>">
                    Next
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <span class="faint">
                    Next
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
            <?php endif ?>
        </li>
    </ul>
</nav>
