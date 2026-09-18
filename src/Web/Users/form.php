<?php

declare(strict_types=1);

use App\Access\RolePermissions;
use App\User\User;
use App\User\UserRole;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<string> $errors
 * @var User|null $user
 * @var array{displayName: string, email: string, role: UserRole, isActive: bool, username?: string} $values
 * @var RolePermissions $rolePermissions
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$isEdit = $user !== null;

$this->setTitle($isEdit ? 'Edit user' : 'New user');

$action = $isEdit
    ? $urlGenerator->generate('user-edit-submit', ['id' => $user->id])
    : $urlGenerator->generate('user-create-submit');
?>

<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= $urlGenerator->generate('user-list') ?>">Users</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span class="faint"><?= $isEdit ? Html::encode($user->username) : 'New user' ?></span>
</nav>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isEdit ? 'Edit user' : 'New user' ?></h1>
        <p class="page-subtitle">Account details, role and password.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <span><?= Html::encode($error) ?></span>
    </div>
<?php endforeach ?>

<div class="split">
    <form method="post" action="<?= $action ?>">
        <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                        Account
                    </h2>
                    <p class="panel-subtitle">How this person signs in and what they may do.</p>
                </div>
            </div>

            <div class="form-grid">
                <?php if (!$isEdit): ?>
                    <div class="field">
                        <label class="field-label" for="username">Username</label>
                        <input type="text" id="username" name="username"
                               value="<?= Html::encode($values['username'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label class="field-label" for="password">Password</label>
                        <input type="password" id="password" name="password" minlength="8" required>
                        <span class="field-hint">Minimum 8 characters.</span>
                    </div>
                <?php else: ?>
                    <div class="field">
                        <span class="field-label">Username</span>
                        <p class="mono"><?= Html::encode($user->username) ?></p>
                    </div>
                <?php endif ?>

                <div class="field">
                    <label class="field-label" for="displayName">Display name</label>
                    <input type="text" id="displayName" name="displayName"
                           value="<?= Html::encode($values['displayName']) ?>">
                </div>

                <div class="field">
                    <label class="field-label" for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= Html::encode($values['email']) ?>">
                </div>

                <div class="field">
                    <label class="field-label" for="role">Role</label>
                    <select id="role" name="role">
                        <?php foreach (UserRole::cases() as $role): ?>
                            <option value="<?= $role->value ?>" <?= $role === $values['role'] ? 'selected' : '' ?>>
                                <?= Html::encode($role->label()) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="field field-checkbox">
                    <input type="checkbox" id="isActive" name="isActive" value="1"
                           <?= $values['isActive'] ? 'checked' : '' ?>>
                    <label class="field-label" for="isActive">Active</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    <?= $isEdit ? 'Save changes' : 'Create user' ?>
                </button>
                <a class="button button-quiet" href="<?= $urlGenerator->generate('user-list') ?>">Cancel</a>
            </div>
        </div>
    </form>

    <div>
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        Permissions per role
                    </h2>
                </div>
            </div>

            <table class="table">
                <thead>
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (UserRole::cases() as $role): ?>
                    <tr>
                        <td><span class="cell-title"><?= Html::encode($role->label()) ?></span></td>
                        <td>
                            <span class="product-meta">
                                <?php foreach ($rolePermissions->all($role) as $permission): ?>
                                    <span class="badge badge-info"><?= Html::encode($permission->value) ?></span>
                                <?php endforeach ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php if ($isEdit): ?>
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                            Reset password
                        </h2>
                        <p class="panel-subtitle">The new password replaces the current one immediately.</p>
                    </div>
                </div>

                <form method="post" action="<?= $urlGenerator->generate('user-password', ['id' => $user->id]) ?>"
                      class="stack">
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf ?? '') ?>">
                    <div class="field">
                        <label class="field-label" for="new-password">New password</label>
                        <input type="password" id="new-password" name="password" minlength="8" required>
                    </div>
                    <button type="submit" class="button button-quiet">
                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                        Set password
                    </button>
                </form>
            </div>
        <?php endif ?>
    </div>
</div>
