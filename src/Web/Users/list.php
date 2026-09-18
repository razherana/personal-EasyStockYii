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

$initials = static function (string $name): string {
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($words, 0, 2) as $word) {
        $letters .= mb_strtoupper(mb_substr($word, 0, 1));
    }

    return $letters === '' ? '?' : $letters;
};
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle">Accounts that can sign in, with the permissions of their role.</p>
    </div>
    <div class="page-actions">
        <a class="button" href="<?= $urlGenerator->generate('user-create') ?>">
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
            New user
        </a>
    </div>
</div>

<div class="panel panel-flush">
    <table class="table">
        <thead>
        <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <span class="row">
                        <span class="avatar avatar-sm avatar-quiet" aria-hidden="true">
                            <?= Html::encode($initials($user->name())) ?>
                        </span>
                        <span>
                            <span class="cell-title"><?= Html::encode($user->name()) ?></span>
                            <span class="cell-sub mono"><?= Html::encode($user->username) ?></span>
                        </span>
                        <?php if ($user->id === $currentUserId): ?>
                            <span class="badge badge-info">you</span>
                        <?php endif ?>
                    </span>
                </td>
                <td class="faint"><?= Html::encode($user->email ?? '—') ?></td>
                <td><span class="badge"><?= Html::encode($user->role->label()) ?></span></td>
                <td>
                    <?php if ($user->isActive): ?>
                        <span class="badge badge-success">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Active
                        </span>
                    <?php else: ?>
                        <span class="badge badge-danger">
                            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                            Inactive
                        </span>
                    <?php endif ?>
                </td>
                <td class="table-actions">
                    <span class="row row-end">
                        <a class="button button-quiet button-small"
                           href="<?= $urlGenerator->generate('user-edit', ['id' => $user->id]) ?>">Edit</a>
                        <?php if ($user->id !== $currentUserId): ?>
                            <form method="post"
                                  action="<?= $urlGenerator->generate('user-toggle', ['id' => $user->id]) ?>"
                                  class="nowrap">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <button type="submit" class="button-link">
                                    <?= $user->isActive ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        <?php endif ?>
                    </span>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>

<p class="field-hint">
    Deactivated users cannot sign in and lose every permission. At least one active administrator must remain.
</p>
