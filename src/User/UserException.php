<?php

declare(strict_types=1);

namespace App\User;

use RuntimeException;

use function sprintf;

/**
 * Domain errors raised by {@see UserService}.
 */
final class UserException extends RuntimeException
{
    public static function usernameTaken(string $username): self
    {
        return new self(sprintf('Username "%s" is already in use.', $username));
    }

    public static function emailTaken(string $email): self
    {
        return new self(sprintf('Email "%s" is already in use.', $email));
    }

    public static function passwordTooShort(int $minimumLength): self
    {
        return new self(sprintf('The password must be at least %d characters long.', $minimumLength));
    }

    public static function notFound(int $id): self
    {
        return new self(sprintf('User #%d was not found.', $id));
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('You cannot deactivate your own account.');
    }

    public static function cannotChangeOwnRole(): self
    {
        return new self('You cannot change the role of your own account.');
    }

    public static function lastAdmin(): self
    {
        return new self('At least one active administrator must remain.');
    }
}
