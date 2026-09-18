<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $permission
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Access denied');
?>

<div class="status-panel">
    <span class="status-icon status-icon-danger">
        <i class="fa-solid fa-lock" aria-hidden="true"></i>
    </span>
    <h1 class="status-title">Access denied</h1>
    <p class="status-text">
        Your account does not allow this action<?= $permission !== '' ? ' (' . Html::encode($permission) . ')' : '' ?>.
    </p>
    <p><a class="button" href="/">Back to dashboard</a></p>
</div>
