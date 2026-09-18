<?php

declare(strict_types=1);

use App\User\CurrentUserProvider;
use App\User\User;
use App\User\UserRole;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<User> $users
 * @var list<UserRole> $roles
 * @var CurrentUserProvider $currentUser
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Users');

$csrf = Html::encode($csrf ?? '');
$currentUserId = $currentUser->id();
?>

<div class="spread">
    <h1>Users</h1>
    <a class="button" href="<?= $urlGenerator->generate('user-create') ?>">New user</a>
</div>

<table class="table">
    <thead>
    <tr>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td class="mono">
                <?= Html::encode($user->username) ?>
                <?php if ($user->id === $currentUserId): ?>
                    <span class="badge">you</span>
                <?php endif ?>
            </td>
            <td><?= Html::encode($user->name()) ?></td>
            <td><?= Html::encode($user->email ?? '—') ?></td>
            <td><?= Html::encode($user->role->label()) ?></td>
            <td>
                <?php if ($user->isActive): ?>
                    <span class="faint">Active</span>
                <?php else: ?>
                    <span class="badge">Inactive</span>
                <?php endif ?>
            </td>
            <td class="table-actions">
                <a class="button-link" href="<?= $urlGenerator->generate('user-edit', ['id' => $user->id]) ?>">Edit</a>
                <?php if ($user->id !== $currentUserId): ?>
                    <form method="post" action="<?= $urlGenerator->generate('user-toggle', ['id' => $user->id]) ?>"
                          class="nowrap">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <button type="submit" class="button-link">
                            <?= $user->isActive ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<p class="field-hint">
    Deactivated users cannot sign in and lose every permission. At least one active administrator must remain.
</p>
