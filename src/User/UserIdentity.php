<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\Auth\IdentityInterface;

/**
 * Authentication identity of a logged-in user.
 */
final readonly class UserIdentity implements IdentityInterface
{
    public function __construct(
        public User $user,
    ) {}

    public function getId(): ?string
    {
        return (string) $this->user->id;
    }
}
