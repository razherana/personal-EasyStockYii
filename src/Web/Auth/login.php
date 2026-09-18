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

<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <span class="login-brand-mark" aria-hidden="true">ES</span>
            <span class="login-brand-name">EasyStock</span>
        </div>

        <h1 class="login-title">Sign in</h1>
        <p class="login-subtitle">Welcome back. Sign in to manage the catalogue and the stock ledger.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= Html::encode($error) ?></span>
            </div>
        <?php endforeach ?>

        <form method="post" action="<?= $urlGenerator->generate('login-submit') ?>" class="login-form">
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">

            <div class="field">
                <label class="field-label" for="username">Username</label>
                <span class="input-icon">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <input type="text" id="username" name="username" autocomplete="username"
                           placeholder="Your username" autofocus required>
                </span>
            </div>

            <div class="field">
                <label class="field-label" for="password">Password</label>
                <span class="input-icon">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input type="password" id="password" name="password" autocomplete="current-password"
                           placeholder="Your password" required>
                    <button type="button"
                            class="icon-button icon-button-quiet password-toggle"
                            data-password-toggle="password"
                            aria-pressed="false"
                            title="Show password">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        <span class="visually-hidden">Show password</span>
                    </button>
                </span>
            </div>

            <button type="submit" class="button button-block">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                Sign in
            </button>
        </form>

        <p class="login-hint">
            Accounts are created by an administrator. Run <code>./yii user:create</code> to create the first one.
        </p>
    </div>
</div>
