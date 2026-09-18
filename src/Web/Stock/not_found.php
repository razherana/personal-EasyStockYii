<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('Variant not found');
?>

<div class="panel">
    <h1>Variant not found</h1>
    <p class="muted">This variant does not exist or was removed.</p>
    <p><a class="button" href="/stock">Back to stock</a></p>
</div>
