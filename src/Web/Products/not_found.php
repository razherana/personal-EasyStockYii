<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('Product not found');
?>

<div class="panel">
    <h1>Product not found</h1>
    <p class="muted">The product you asked for does not exist or was removed.</p>
    <p><a class="button" href="/products">Back to products</a></p>
</div>
