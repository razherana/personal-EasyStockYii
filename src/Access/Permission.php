<?php

declare(strict_types=1);

namespace App\Access;

/**
 * Every action that can be allowed or denied in the application.
 *
 * Role to permission mapping lives in `config/common/params.php` under the `permissions` key.
 */
enum Permission: string
{
    case ProductView = 'product:view';
    case ProductManage = 'product:manage';
    case OptionManage = 'option:manage';
    case StockView = 'stock:view';
    case StockOperate = 'stock:operate';
    case Export = 'export';
    case Import = 'import';
    case UserManage = 'user:manage';

    public function label(): string
    {
        return match ($this) {
            self::ProductView => 'View products',
            self::ProductManage => 'Manage products',
            self::OptionManage => 'Manage option types',
            self::StockView => 'View stock',
            self::StockOperate => 'Record stock movements',
            self::Export => 'Export data',
            self::Import => 'Import data',
            self::UserManage => 'Manage users',
        };
    }
}
