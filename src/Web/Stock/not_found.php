<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('Variant not found');
?>

<div class="status-panel">
    <span class="status-icon status-icon-warning">
        <i class="fa-solid fa-boxes-packing" aria-hidden="true"></i>
    </span>
    <h1 class="status-title">Variant not found</h1>
    <p class="status-text">This variant does not exist or was removed.</p>
    <p><a class="button" href="/stock">Back to stock</a></p>
</div>
