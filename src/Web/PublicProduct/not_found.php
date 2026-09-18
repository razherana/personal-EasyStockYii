<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('Product not found');
?>

<header class="public-header">
    <span class="public-brand">
        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
        Product summary
    </span>
    <h1 class="public-title">Product not found</h1>
    <p class="public-meta">This QR code does not point to a product of this catalogue.</p>
</header>
