<?php

declare(strict_types=1);

namespace App\Export;

/**
 * The data sets that can be exported.
 */
enum ExportDatasetName: string
{
    case Products = 'products';
    case Stock = 'stock';
    case Movements = 'movements';
    case ImportTemplate = 'import-template';

    public function label(): string
    {
        return match ($this) {
            self::Products => 'Products',
            self::Stock => 'Stock levels',
            self::Movements => 'Stock movements',
            self::ImportTemplate => 'Import template',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Products => 'One row per product with unit, threshold, variant count and stock on hand.',
            self::Stock => 'One row per variant with its option values and current stock level.',
            self::Movements => 'The stock ledger: every movement with type, change, reference and author.',
            self::ImportTemplate => 'Empty sheet with the columns the import expects, plus one example row.',
        };
    }

    public function fileBaseName(): string
    {
        return $this->value;
    }
}
