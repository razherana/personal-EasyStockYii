<?php

declare(strict_types=1);

namespace App\Migrations;

use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Creates the stock movement ledger. Quantities are stored as signed changes, so the current
 * stock level of a variant is the sum of its movements.
 */
final class M20260917000004CreateStockMovementTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    use References;

    public function up(MigrationBuilder $b): void
    {
        $columnBuilder = $b->columnBuilder();

        $b->createTable('stock_movement', [
            'id' => $columnBuilder::primaryKey(),
            'variant_id' => $columnBuilder::integer()->notNull()->reference(self::reference('variant')),
            'type' => $columnBuilder::string(16)->notNull(),
            'quantity_change' => $columnBuilder::integer()->notNull(),
            'unit_cost' => $columnBuilder::decimal(10, 2),
            'reference' => $columnBuilder::string(120),
            'note' => $columnBuilder::text(),
            'created_by' => $columnBuilder::integer()
                ->reference(self::reference('user', ReferentialAction::SET_NULL)),
            'created_at' => $columnBuilder::datetime()->notNull(),
        ]);

        $b->createIndex('stock_movement', 'idx_stock_movement_variant', ['variant_id', 'created_at']);
        $b->createIndex('stock_movement', 'idx_stock_movement_created_at', ['created_at']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('stock_movement');
    }
}
