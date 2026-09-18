<?php

declare(strict_types=1);

namespace App\Migrations;

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Creates the variant tables: one row per product option combination and its option values.
 */
final class M20260917000003CreateVariantTables implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    use References;

    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('variant', [
            'id' => $columnBuilder::primaryKey(),
            'product_id' => $columnBuilder::integer()->notNull()->reference(self::reference('product')),
            'sku' => $columnBuilder::string(96)->notNull()->unique(),
            'is_default' => $columnBuilder::boolean()->notNull()->defaultValue(false),
            'is_active' => $columnBuilder::boolean()->notNull()->defaultValue(true),
            'created_at' => $columnBuilder::datetime()->notNull(),
            'updated_at' => $columnBuilder::datetime()->notNull(),
        ]);

        $b->createTable('variant_option_value', [
            'id' => $columnBuilder::primaryKey(),
            'variant_id' => $columnBuilder::integer()->notNull()->reference(self::reference('variant')),
            'option_value_id' => $columnBuilder::integer()
                ->notNull()
                ->reference(self::reference('option_value', ReferentialAction::RESTRICT)),
        ]);

        $b->createIndex(
            'variant_option_value',
            'uq_variant_option_value',
            ['variant_id', 'option_value_id'],
            IndexType::UNIQUE,
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('variant_option_value');
        $b->dropTable('variant');
    }
}
