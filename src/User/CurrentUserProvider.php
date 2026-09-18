<?php

declare(strict_types=1);

namespace App\User;

use App\Access\Permission;
use Yiisoft\User\CurrentUser;

/**
 * Convenience access to the domain user behind the current session.
 *
 * Actions and templates use this instead of dealing with identities directly.
 */
final readonly class CurrentUserProvider
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $users,
    ) {}

    public function isGuest(): bool
    {
        return $this->currentUser->isGuest();
    }

    public function user(): ?User
    {
        $id = $this->currentUser->getId();

        return $id === null ? null : $this->users->findById((int) $id);
    }

    public function id(): ?int
    {
        $id = $this->currentUser->getId();

        return $id === null ? null : (int) $id;
    }

    public function can(Permission $permission): bool
    {
        return !$this->isGuest() && $this->currentUser->can($permission);
    }

    public function isAdmin(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function login(User $user): bool
    {
        return $this->currentUser->login(new UserIdentity($user));
    }

    public function logout(): bool
    {
        return $this->currentUser->logout();
    }
}
