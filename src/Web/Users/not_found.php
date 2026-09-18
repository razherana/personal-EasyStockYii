<?php

declare(strict_types=1);

use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('User not found');
?>

<div class="status-panel">
    <span class="status-icon status-icon-warning">
        <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
    </span>
    <h1 class="status-title">User not found</h1>
    <p class="status-text">This account does not exist.</p>
    <p><a class="button" href="/users">Back to users</a></p>
</div>
