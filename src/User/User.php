<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Database\Row;
use DateTimeImmutable;

/**
 * A user account.
 *
 * @psalm-type UserRow = array<array-key, mixed>
 */
final readonly class User
{
    public function __construct(
        public int $id,
        public string $username,
        public string $passwordHash,
        public string $displayName,
        public ?string $email,
        public UserRole $role,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @psalm-param UserRow|object $row
     */
    public static function hydrate(array|object $row): self
    {
        /** @var array<array-key, mixed> $row */
        $row = (array) $row;

        return new self(
            id: Row::int($row, 'id'),
            username: Row::string($row, 'username'),
            passwordHash: Row::string($row, 'password_hash'),
            displayName: Row::string($row, 'display_name'),
            email: Row::nullableString($row, 'email'),
            role: UserRole::from(Row::string($row, 'role')),
            isActive: Row::bool($row, 'is_active'),
            createdAt: Row::dateTime($row, 'created_at'),
            updatedAt: Row::dateTime($row, 'updated_at'),
        );
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function name(): string
    {
        return $this->displayName === '' ? $this->username : $this->displayName;
    }
}
