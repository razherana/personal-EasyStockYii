<?php

declare(strict_types=1);

namespace App\Export;

use App\Products\OptionRepository;
use App\Products\ProductRepository;
use App\Stock\StockRepository;
use App\Stock\StockLevel;

use function array_map;
use function implode;

/**
 * Builds the export data sets out of the repositories.
 */
final readonly class DatasetBuilder
{
    private const MAX_ROWS = 10000;

    public function __construct(
        private ProductRepository $products,
        private StockRepository $stock,
        private OptionRepository $options,
    ) {}

    public function build(ExportDatasetName $name): ExportDataset
    {
        return match ($name) {
            ExportDatasetName::Products => $this->products(),
            ExportDatasetName::Stock => $this->stock(),
            ExportDatasetName::Movements => $this->movements(),
            ExportDatasetName::ImportTemplate => $this->importTemplate(),
        };
    }

    public function products(): ExportDataset
    {
        $dataset = new ExportDataset(
            title: 'Products',
            fileBaseName: ExportDatasetName::Products->fileBaseName(),
            headers: ['sku', 'name', 'unit', 'low_stock_threshold', 'variants', 'on_hand', 'active'],
            rows: [],
        );

        foreach ($this->products->search('', true, self::MAX_ROWS) as $item) {
            $dataset = $dataset->addRow([
                'sku' => $item->product->sku,
                'name' => $item->product->name,
                'unit' => $item->product->unit,
                'low_stock_threshold' => $item->product->lowStockThreshold,
                'variants' => $item->variantCount,
                'on_hand' => $item->onHand,
                'active' => $item->product->isActive ? 1 : 0,
            ]);
        }

        return $dataset;
    }

    public function stock(): ExportDataset
    {
        $dataset = new ExportDataset(
            title: 'Stock levels',
            fileBaseName: ExportDatasetName::Stock->fileBaseName(),
            headers: ['product_sku', 'product', 'variant_sku', 'options', 'on_hand', 'unit', 'low_stock_threshold',
                'low_stock', 'last_movement'],
            rows: [],
        );

        $levels = $this->stock->levels('', false, true, self::MAX_ROWS);
        $variantIds = array_map(static fn(StockLevel $level): int => $level->variantId, $levels);
        $valuesByVariant = $this->options->valuesByVariant($variantIds);
        $typeNames = $this->options->typeNames();

        foreach ($levels as $level) {
            $labels = [];

            foreach ($valuesByVariant[$level->variantId] ?? [] as $value) {
                $labels[] = ($typeNames[$value->optionTypeId] ?? 'Option') . ': ' . $value->value;
            }

            $dataset = $dataset->addRow([
                'product_sku' => $level->productSku,
                'product' => $level->productName,
                'variant_sku' => $level->variantSku,
                'options' => implode('; ', $labels),
                'on_hand' => $level->onHand,
                'unit' => $level->productUnit,
                'low_stock_threshold' => $level->lowStockThreshold,
                'low_stock' => $level->isLowStock() ? 1 : 0,
                'last_movement' => $level->lastMovementAt?->format('Y-m-d H:i') ?? '',
            ]);
        }

        return $dataset;
    }

    public function movements(int $limit = 1000): ExportDataset
    {
        $dataset = new ExportDataset(
            title: 'Stock movements',
            fileBaseName: ExportDatasetName::Movements->fileBaseName(),
            headers: ['date', 'product', 'variant_sku', 'type', 'change', 'reference', 'note', 'unit_cost', 'user'],
            rows: [],
        );

        foreach ($this->stock->latestMovementItems($limit) as $item) {
            $dataset = $dataset->addRow([
                'date' => $item->movement->createdAt->format('Y-m-d H:i'),
                'product' => $item->productName,
                'variant_sku' => $item->variantSku,
                'type' => $item->movement->type->value,
                'change' => $item->movement->quantityChange,
                'reference' => $item->movement->reference ?? '',
                'note' => $item->movement->note ?? '',
                'unit_cost' => $item->movement->unitCost,
                'user' => $item->movement->createdByName,
            ]);
        }

        return $dataset;
    }

    public function importTemplate(): ExportDataset
    {
        return new ExportDataset(
            title: 'Product import template',
            fileBaseName: ExportDatasetName::ImportTemplate->fileBaseName(),
            headers: ['sku', 'name', 'unit', 'low_stock_threshold', 'active', 'options', 'quantity'],
            rows: [[
                'sku' => 'TSHIRT-XL-RED',
                'name' => 'T-Shirt XL red',
                'unit' => 'piece',
                'low_stock_threshold' => 5,
                'active' => 1,
                'options' => 'Size=XL; Color=Red',
                'quantity' => 10,
            ]],
        );
    }
}
