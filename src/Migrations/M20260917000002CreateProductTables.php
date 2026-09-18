<?php

declare(strict_types=1);

namespace App\Migrations;

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Creates the product catalogue tables: products, option types and option values.
 */
final class M20260917000002CreateProductTables implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    use References;

    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('product', [
            'id' => $columnBuilder::primaryKey(),
            'sku' => $columnBuilder::string(64)->notNull()->unique(),
            'name' => $columnBuilder::string(190)->notNull(),
            'description' => $columnBuilder::text(),
            'unit' => $columnBuilder::string(32)->notNull()->defaultValue('piece'),
            'qr_token' => $columnBuilder::string(64)->notNull()->unique(),
            'low_stock_threshold' => $columnBuilder::integer(),
            'is_active' => $columnBuilder::boolean()->notNull()->defaultValue(true),
            'created_at' => $columnBuilder::datetime()->notNull(),
            'updated_at' => $columnBuilder::datetime()->notNull(),
        ]);

        $b->createTable('option_type', [
            'id' => $columnBuilder::primaryKey(),
            'name' => $columnBuilder::string(64)->notNull()->unique(),
            'position' => $columnBuilder::integer()->notNull()->defaultValue(0),
        ]);

        $b->createTable('option_value', [
            'id' => $columnBuilder::primaryKey(),
            'option_type_id' => $columnBuilder::integer()->notNull()->reference(self::reference('option_type')),
            'value' => $columnBuilder::string(64)->notNull(),
            'position' => $columnBuilder::integer()->notNull()->defaultValue(0),
        ]);

        $b->createIndex(
            'option_value',
            'uq_option_value_type_value',
            ['option_type_id', 'value'],
            IndexType::UNIQUE,
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('option_value');
        $b->dropTable('option_type');
        $b->dropTable('product');
    }
}
