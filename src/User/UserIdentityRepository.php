<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;

use function is_numeric;

/**
 * Loads authentication identities by their ID, as stored in the session.
 */
final readonly class UserIdentityRepository implements IdentityRepositoryInterface
{
    public function __construct(
        private UserRepository $users,
    ) {}

    public function findIdentity(string $id): ?IdentityInterface
    {
        if (!is_numeric($id)) {
            return null;
        }

        $user = $this->users->findById((int) $id);

        if ($user === null || !$user->isActive) {
            return null;
        }

        return new UserIdentity($user);
    }
}
