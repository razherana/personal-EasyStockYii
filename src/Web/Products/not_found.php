<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('Product not found');
?>

<div class="status-panel">
    <span class="status-icon status-icon-warning">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
    </span>
    <h1 class="status-title">Product not found</h1>
    <p class="status-text">The product you asked for does not exist or was removed.</p>
    <p><a class="button" href="/products">Back to products</a></p>
</div>
