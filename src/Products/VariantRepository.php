<?php

declare(strict_types=1);

namespace App\Products;

use App\Shared\Database\Timestamps;
use Yiisoft\Db\Connection\ConnectionInterface;

use function array_map;
use function implode;

use const SORT_ASC;

/**
 * Reads and writes product variants and their option value links.
 */
final readonly class VariantRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private OptionRepository $options,
    ) {}

    public function findById(int $id): ?ProductVariant
    {
        $row = $this->db->createQuery()->select('*')->from('variant')->where(['id' => $id])->one();

        return $row === null ? null : $this->withOptionData(ProductVariant::hydrate($row));
    }

    public function findBySku(string $sku): ?ProductVariant
    {
        $row = $this->db->createQuery()->select('*')->from('variant')->where(['sku' => $sku])->one();

        return $row === null ? null : $this->withOptionData(ProductVariant::hydrate($row));
    }

    /**
     * @return list<ProductVariant> Variants of the product ordered by SKU, including inactive ones.
     */
    public function findByProductId(int $productId, bool $onlyActive = false): array
    {
        $query = $this->db
            ->createQuery()
            ->select('*')
            ->from('variant')
            ->where(['product_id' => $productId]);

        if ($onlyActive) {
            $query->andWhere(['is_active' => 1]);
        }

        $rows = $query->orderBy(['sku' => SORT_ASC])->all();

        $variants = [];

        foreach ($rows as $row) {
            $variants[] = ProductVariant::hydrate($row);
        }

        return $this->withOptionDataForAll($variants);
    }

    /**
     * Variants with their option values and stock, for the product detail page.
     *
     * @return list<ProductVariant>
     */
    public function findByProductIdWithOptions(int $productId, bool $onlyActive = false): array
    {
        return $this->findByProductId($productId, $onlyActive);
    }

    /**
     * Option value IDs used by the active variants of a product, for the edit form.
     *
     * @return list<int>
     */
    public function optionValueIdsForProduct(int $productId): array
    {
        $rows = $this->db->createCommand(
            'SELECT DISTINCT vov.[[option_value_id]] AS [[option_value_id]]'
            . ' FROM {{%variant_option_value}} vov'
            . ' INNER JOIN {{%variant}} v ON v.[[id]] = vov.[[variant_id]]'
            . ' WHERE v.[[product_id]] = :productId AND v.[[is_active]] = 1',
            ['productId' => $productId],
        )->queryColumn();

        $ids = [];

        foreach ($rows as $row) {
            $ids[] = (int) $row;
        }

        return $ids;
    }

    public function insert(ProductVariant $variant): int
    {
        $this->db->createCommand()->insert('variant', [
            'product_id' => $variant->productId,
            'sku' => $variant->sku,
            'is_default' => $variant->isDefault ? 1 : 0,
            'is_active' => $variant->isActive ? 1 : 0,
            'created_at' => Timestamps::format($variant->createdAt),
            'updated_at' => Timestamps::format($variant->updatedAt),
        ])->execute();

        return (int) $this->db->getLastInsertId();
    }

    public function update(ProductVariant $variant): void
    {
        $this->db->createCommand()->update('variant', [
            'sku' => $variant->sku,
            'is_default' => $variant->isDefault ? 1 : 0,
            'is_active' => $variant->isActive ? 1 : 0,
            'updated_at' => Timestamps::format($variant->updatedAt),
        ], ['id' => $variant->id])->execute();
    }

    /**
     * @param list<int> $optionValueIds
     */
    public function setOptionValues(int $variantId, array $optionValueIds): void
    {
        $this->deleteOptionValues($variantId);

        foreach (array_map(static fn(int $id): int => $id, $optionValueIds) as $optionValueId) {
            $this->db->createCommand()->insert('variant_option_value', [
                'variant_id' => $variantId,
                'option_value_id' => $optionValueId,
            ])->execute();
        }
    }

    public function deleteOptionValues(int $variantId): void
    {
        $this->db->createCommand()->delete('variant_option_value', ['variant_id' => $variantId])->execute();
    }

    public function countActive(): int
    {
        return (int) $this->db->createQuery()->from('variant')->where(['is_active' => 1])->count();
    }

    public function countAll(): int
    {
        return (int) $this->db->createQuery()->from('variant')->count();
    }

    /**
     * Variant IDs of a product, used to fetch stock levels.
     *
     * @return list<int>
     */
    public function idsForProduct(int $productId, bool $onlyActive = true): array
    {
        $query = $this->db->createQuery()->select('id')->from('variant')->where(['product_id' => $productId]);

        if ($onlyActive) {
            $query->andWhere(['is_active' => 1]);
        }

        $ids = [];

        foreach ($query->column() as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, list<OptionValue>>
     */
    private function valuesByVariantIds(array $ids): array
    {
        return $this->options->valuesByVariant($ids);
    }

    private function withOptionData(ProductVariant $variant): ProductVariant
    {
        return $this->withOptionDataForAll([$variant])[0] ?? $variant;
    }

    /**
     * @param list<ProductVariant> $variants
     *
     * @return list<ProductVariant>
     */
    private function withOptionDataForAll(array $variants): array
    {
        if ($variants === []) {
            return [];
        }

        $ids = [];

        foreach ($variants as $variant) {
            $ids[] = $variant->id;
        }

        $valuesByVariant = $this->valuesByVariantIds($ids);
        $typeNames = $this->options->typeNames();
        $result = [];

        foreach ($variants as $variant) {
            $values = $valuesByVariant[$variant->id] ?? [];
            $valueIds = [];
            $labels = [];

            foreach ($values as $value) {
                $valueIds[] = $value->id;
                $labels[] = ($typeNames[$value->optionTypeId] ?? 'Option') . ': ' . $value->value;
            }

            $result[] = $variant->withOptionData($valueIds, $labels);
        }

        return $result;
    }

    /**
     * @param list<int> $ids
     */
    private static function placeholders(array $ids): string
    {
        return implode(',', array_map(static fn(int $id): int => $id, $ids));
    }
}
