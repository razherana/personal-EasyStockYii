<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('User not found');
?>

<div class="panel">
    <h1>User not found</h1>
    <p class="muted">This account does not exist.</p>
    <p><a class="button" href="/users">Back to users</a></p>
</div>
