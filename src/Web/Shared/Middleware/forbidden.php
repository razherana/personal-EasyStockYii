<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $permission
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Access denied');
?>

<div class="panel">
    <h1>Access denied</h1>
    <p>Your account does not allow this action<?= $permission !== '' ? ' (' . Html::encode($permission) . ')' : '' ?>.</p>
    <p><a class="button" href="/">Back to dashboard</a></p>
</div>
