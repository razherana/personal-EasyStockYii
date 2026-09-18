<?php

declare(strict_types=1);

namespace App\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Creates the `user` table: application accounts, their role and active state.
 */
final class M20260917000001CreateUserTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('user', [
            'id' => $columnBuilder::primaryKey(),
            'username' => $columnBuilder::string(64)->notNull()->unique(),
            'password_hash' => $columnBuilder::string(255)->notNull(),
            'display_name' => $columnBuilder::string(128)->notNull()->defaultValue(''),
            'email' => $columnBuilder::string(190)->unique(),
            'role' => $columnBuilder::string(20)->notNull()->defaultValue('staff'),
            'is_active' => $columnBuilder::boolean()->notNull()->defaultValue(true),
            'created_at' => $columnBuilder::datetime()->notNull(),
            'updated_at' => $columnBuilder::datetime()->notNull(),
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('user');
    }
}
