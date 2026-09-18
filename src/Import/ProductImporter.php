<?php

declare(strict_types=1);

namespace App\Import;

use App\Products\OptionRepository;
use App\Products\ProductData;
use App\Products\ProductException;
use App\Products\ProductRepository;
use App\Products\ProductService;
use App\Products\VariantRepository;
use App\Stock\MovementType;
use App\Stock\StockException;
use App\Stock\StockService;
use Throwable;

use function count;
use function explode;
use function filter_var;
use function is_int;
use function sprintf;
use function trim;

use const FILTER_VALIDATE_INT;

/**
 * Imports products and their option values from a spreadsheet.
 *
 * Expected columns: `sku`, `name`, `unit`, `low_stock_threshold`, `active`, `options`, `quantity`.
 * Option values use the form `Size=XL; Color=Red`. Missing option types and values are created.
 * The initial `quantity` is only applied when the imported product has exactly one variant.
 */
final readonly class ProductImporter
{
    public function __construct(
        private OptionRepository $options,
        private ProductRepository $products,
        private VariantRepository $variants,
        private ProductService $productService,
        private StockService $stockService,
    ) {}

    /**
     * @param iterable<int, array<string, string>> $rows Rows keyed by their line number.
     */
    public function import(iterable $rows, int $userId): ImportResult
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowCount = 0;

        foreach ($rows as $line => $row) {
            $rowCount++;

            $sku = trim($row['sku'] ?? '');
            $name = trim($row['name'] ?? '');

            if ($sku === '') {
                $errors[] = sprintf('Line %d: the "sku" column is required.', $line);

                continue;
            }

            if ($name === '') {
                $errors[] = sprintf('Line %d (%s): the "name" column is required.', $line, $sku);

                continue;
            }

            try {
                $optionValueIds = $this->resolveOptionValues($row['options'] ?? '');
            } catch (ProductException $exception) {
                $errors[] = sprintf('Line %d (%s): %s', $line, $sku, $exception->getMessage());

                continue;
            }

            $data = new ProductData(
                sku: $sku,
                name: $name,
                unit: trim($row['unit'] ?? '') === '' ? 'piece' : trim($row['unit'] ?? ''),
                description: null,
                lowStockThreshold: $this->threshold($row['low_stock_threshold'] ?? ''),
                isActive: $this->isActive($row['active'] ?? '1'),
                optionValueIds: $optionValueIds,
            );

            $existing = $this->products->findBySku($sku);

            try {
                $product = $existing === null
                    ? $this->productService->create($data)
                    : $this->productService->update($existing->id, $data);
            } catch (ProductException $exception) {
                $errors[] = sprintf('Line %d (%s): %s', $line, $sku, $exception->getMessage());

                continue;
            }

            if ($existing === null) {
                $created++;
            } else {
                $updated++;
            }

            $quantity = $this->quantity($row['quantity'] ?? '');

            if ($quantity === null || $quantity === 0) {
                continue;
            }

            $variants = $this->variants->findByProductId($product->id, true);

            if (count($variants) !== 1) {
                $skipped++;
                $errors[] = sprintf(
                    'Line %d (%s): the quantity was not applied because the product has %d variants; record the stock per variant instead.',
                    $line,
                    $sku,
                    count($variants),
                );

                continue;
            }

            try {
                $this->stockService->record(
                    type: MovementType::In,
                    variantId: $variants[0]->id,
                    quantity: $quantity,
                    reference: 'Import',
                    note: null,
                    unitCost: null,
                    userId: $userId,
                );
            } catch (StockException|Throwable $exception) {
                $errors[] = sprintf('Line %d (%s): %s', $line, $sku, $exception->getMessage());
            }
        }

        return new ImportResult(
            rows: $rowCount,
            created: $created,
            updated: $updated,
            skipped: $skipped,
            errors: $errors,
        );
    }

    /**
     * @return list<int>
     */
    private function resolveOptionValues(string $options): array
    {
        $ids = [];

        foreach ($this->splitOptions($options) as [$typeName, $valueName]) {
            $typeId = $this->findOrCreateType($typeName);

            if (!$this->options->existsValue($typeId, $valueName)) {
                $this->options->createValue($typeId, $valueName);
            }

            $value = null;

            foreach ($this->options->findValues($typeId) as $optionValue) {
                if ($optionValue->value === $valueName) {
                    $value = $optionValue;
                }
            }

            if ($value === null) {
                throw ProductException::optionValueNotFound($typeName, $valueName);
            }

            $ids[] = $value->id;
        }

        return $ids;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function splitOptions(string $options): array
    {
        $options = trim($options);

        if ($options === '') {
            return [];
        }

        $pairs = [];

        foreach (explode(';', $options) as $chunk) {
            $chunk = trim($chunk);

            if ($chunk === '') {
                continue;
            }

            [$type, $value] = $this->splitPair($chunk);

            $pairs[] = [$type, $value];
        }

        return $pairs;
    }

    private function findOrCreateType(string $name): int
    {
        foreach ($this->options->findTypes() as $type) {
            if ($type->name === $name) {
                return $type->id;
            }
        }

        return $this->options->createType($name);
    }

    /**
     * Splits an "Type=Value" chunk of the options column.
     *
     * @return array{0: string, 1: string}
     */
    private function splitPair(string $chunk): array
    {
        $parts = explode('=', $chunk, 2);
        $type = trim($parts[0]);
        $value = trim($parts[1] ?? '');

        if ($type === '' || $value === '') {
            throw ProductException::invalidOption($chunk);
        }

        return [$type, $value];
    }

    private function threshold(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($parsed) && $parsed >= 0 ? $parsed : null;
    }

    private function quantity(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($parsed) && $parsed > 0 ? $parsed : null;
    }

    private function isActive(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        return $value !== '0' && $value !== 'false';
    }
}
