<?php

declare(strict_types=1);

namespace App\Migrations;

use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;

/**
 * Builds inline foreign keys for migration columns.
 */
trait References
{
    /**
     * @psalm-param ReferentialAction::* $onDelete
     */
    private static function reference(string $table, string $onDelete = ReferentialAction::CASCADE): ForeignKey
    {
        return new ForeignKey(
            foreignTableName: $table,
            foreignColumnNames: ['id'],
            onDelete: $onDelete,
        );
    }
}
