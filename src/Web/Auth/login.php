<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<string> $errors
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Sign in');
?>

<div class="login-card">
    <div class="login-brand">
        <span class="login-brand-mark">ES</span>
        <strong>EasyStock</strong>
    </div>

    <h1 class="login-title">Sign in</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" role="alert"><?= Html::encode($error) ?></div>
    <?php endforeach ?>

    <form method="post" action="<?= $urlGenerator->generate('login-submit') ?>" class="stack">
        <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">

        <div class="field">
            <label class="field-label" for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" autofocus required>
        </div>

        <div class="field">
            <label class="field-label" for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="button">Sign in</button>
    </form>

    <p class="login-hint">
        Accounts are created by an administrator. Run <code>./yii user:create</code> to create the first one.
    </p>
</div>
