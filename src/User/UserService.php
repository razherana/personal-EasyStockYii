<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Database\Timestamps;
use Yiisoft\Security\PasswordHasher;

use function mb_strlen;
use function trim;

/**
 * Creates, updates and authenticates user accounts.
 */
final readonly class UserService
{
    public const MINIMUM_PASSWORD_LENGTH = 8;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
    ) {}

    public function create(
        string $username,
        string $password,
        string $displayName,
        ?string $email,
        UserRole $role,
        bool $isActive = true,
    ): User {
        $username = trim($username);
        $email = $this->normalizeEmail($email);
        $this->assertPassword($password);
        $this->assertUnique($username, $email);

        $now = Timestamps::now();

        $user = new User(
            id: 0,
            username: $username,
            passwordHash: $this->passwordHasher->hash($password),
            displayName: trim($displayName),
            email: $email,
            role: $role,
            isActive: $isActive,
            createdAt: $now,
            updatedAt: $now,
        );

        $id = $this->users->insert($user);

        return $this->users->findById($id) ?? throw UserException::notFound($id);
    }

    public function update(
        int $id,
        string $displayName,
        ?string $email,
        UserRole $role,
        bool $isActive,
        int $actingUserId,
    ): User {
        $user = $this->requireUser($id);
        $email = $this->normalizeEmail($email);

        if ($email !== null && $this->users->existsByEmail($email, $id)) {
            throw UserException::emailTaken($email);
        }

        if (!$isActive && $id === $actingUserId) {
            throw UserException::cannotDeactivateSelf();
        }

        if ($role !== $user->role && $id === $actingUserId) {
            throw UserException::cannotChangeOwnRole();
        }

        if ($user->isAdmin() && (!$role->isAdmin() || !$isActive) && $this->users->countAdmins($id) === 0) {
            throw UserException::lastAdmin();
        }

        $updated = new User(
            id: $user->id,
            username: $user->username,
            passwordHash: $user->passwordHash,
            displayName: trim($displayName),
            email: $email,
            role: $role,
            isActive: $isActive,
            createdAt: $user->createdAt,
            updatedAt: Timestamps::now(),
        );

        $this->users->update($updated);

        return $updated;
    }

    public function changePassword(int $id, string $password): void
    {
        $this->requireUser($id);
        $this->assertPassword($password);

        $this->users->updatePasswordHash($id, $this->passwordHasher->hash($password));
    }

    public function setActive(int $id, bool $isActive, int $actingUserId): void
    {
        $user = $this->requireUser($id);

        if (!$isActive && $id === $actingUserId) {
            throw UserException::cannotDeactivateSelf();
        }

        if ($user->isAdmin() && !$isActive && $this->users->countAdmins($id) === 0) {
            throw UserException::lastAdmin();
        }

        $this->users->update(new User(
            id: $user->id,
            username: $user->username,
            passwordHash: $user->passwordHash,
            displayName: $user->displayName,
            email: $user->email,
            role: $user->role,
            isActive: $isActive,
            createdAt: $user->createdAt,
            updatedAt: Timestamps::now(),
        ));
    }

    public function delete(int $id, int $actingUserId): void
    {
        $user = $this->requireUser($id);

        if ($id === $actingUserId) {
            throw UserException::cannotDeactivateSelf();
        }

        if ($user->isAdmin() && $this->users->countAdmins($id) === 0) {
            throw UserException::lastAdmin();
        }

        $this->users->delete($id);
    }

    /**
     * Returns the user when the credentials are valid and the account is active, `null` otherwise.
     */
    public function authenticate(string $username, string $password): ?User
    {
        $user = $this->users->findByUsername(trim($username));

        if ($user === null || !$user->isActive || $password === '') {
            return null;
        }

        return $this->passwordHasher->validate($password, $user->passwordHash) ? $user : null;
    }

    private function requireUser(int $id): User
    {
        return $this->users->findById($id) ?? throw UserException::notFound($id);
    }

    private function assertPassword(string $password): void
    {
        if (mb_strlen($password) < self::MINIMUM_PASSWORD_LENGTH) {
            throw UserException::passwordTooShort(self::MINIMUM_PASSWORD_LENGTH);
        }
    }

    private function assertUnique(string $username, ?string $email): void
    {
        if ($this->users->existsByUsername($username)) {
            throw UserException::usernameTaken($username);
        }

        if ($email !== null && $this->users->existsByEmail($email)) {
            throw UserException::emailTaken($email);
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = $email === null ? null : trim($email);

        return $email === '' ? null : $email;
    }
}
