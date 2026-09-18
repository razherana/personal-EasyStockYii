<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Database\Timestamps;
use Yiisoft\Db\Connection\ConnectionInterface;

use const SORT_ASC;

/**
 * Reads and writes user accounts.
 */
final readonly class UserRepository
{
    private const TABLE = 'user';

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function findById(int $id): ?User
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * @return list<User>
     */
    public function findAll(): array
    {
        $rows = $this->db
            ->createQuery()
            ->select('*')
            ->from(self::TABLE)
            ->orderBy(['username' => SORT_ASC])
            ->all();

        return $this->hydrateAll($rows);
    }

    public function existsByUsername(string $username, ?int $exceptId = null): bool
    {
        return $this->exists(['username' => $username], $exceptId);
    }

    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        return $this->exists(['email' => $email], $exceptId);
    }

    public function countAdmins(?int $exceptId = null): int
    {
        $query = $this->db
            ->createQuery()
            ->from(self::TABLE)
            ->where(['role' => UserRole::Admin->value, 'is_active' => 1]);

        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }

        return (int) $query->count();
    }

    public function insert(User $user): int
    {
        $this->db->createCommand()->insert(self::TABLE, [
            'username' => $user->username,
            'password_hash' => $user->passwordHash,
            'display_name' => $user->displayName,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->isActive ? 1 : 0,
            'created_at' => Timestamps::format($user->createdAt),
            'updated_at' => Timestamps::format($user->updatedAt),
        ])->execute();

        return (int) $this->db->getLastInsertId();
    }

    public function update(User $user): void
    {
        $this->db->createCommand()->update(self::TABLE, [
            'username' => $user->username,
            'display_name' => $user->displayName,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->isActive ? 1 : 0,
            'updated_at' => Timestamps::format($user->updatedAt),
        ], ['id' => $user->id])->execute();
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $this->db->createCommand()->update(self::TABLE, [
            'password_hash' => $passwordHash,
            'updated_at' => Timestamps::format(Timestamps::now()),
        ], ['id' => $id])->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand()->delete(self::TABLE, ['id' => $id])->execute();
    }

    /**
     * @param array<string, int|string> $condition
     */
    private function findOneBy(array $condition): ?User
    {
        $row = $this->db
            ->createQuery()
            ->select('*')
            ->from(self::TABLE)
            ->where($condition)
            ->one();

        return $row === null ? null : User::hydrate($row);
    }

    /**
     * @param array<string, int|string> $condition
     */
    private function exists(array $condition, ?int $exceptId): bool
    {
        $query = $this->db->createQuery()->from(self::TABLE)->where($condition);

        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }

        return $query->exists();
    }

    /**
     * @param array<array-key, array<array-key, mixed>|object> $rows
     *
     * @return list<User>
     */
    private function hydrateAll(array $rows): array
    {
        $users = [];

        foreach ($rows as $row) {
            $users[] = User::hydrate($row);
        }

        return $users;
    }
}
