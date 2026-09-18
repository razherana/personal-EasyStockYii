<?php

declare(strict_types=1);

namespace App\Products;

use App\Shared\Database\Row;
use App\Shared\Database\Timestamps;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Reads and writes products.
 */
final readonly class ProductRepository
{
    private const TABLE = 'product';

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function findById(int $id): ?Product
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->findOneBy(['sku' => $sku]);
    }

    public function findByQrToken(string $token): ?Product
    {
        return $this->findOneBy(['qr_token' => $token]);
    }

    public function existsBySku(string $sku, ?int $exceptId = null): bool
    {
        $query = $this->db->createQuery()->from(self::TABLE)->where(['sku' => $sku]);

        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }

        return $query->exists();
    }

    public function existsByQrToken(string $token): bool
    {
        return $this->db->createQuery()->from(self::TABLE)->where(['qr_token' => $token])->exists();
    }

    public function countActive(): int
    {
        return (int) $this->db->createQuery()->from(self::TABLE)->where(['is_active' => 1])->count();
    }

    public function countAll(): int
    {
        return (int) $this->db->createQuery()->from(self::TABLE)->count();
    }

    /**
     * Products with their active variant count and stock on hand.
     *
     * @return list<ProductListItem>
     */
    public function search(
        string $search = '',
        bool $includeInactive = false,
        int $limit = 25,
        int $offset = 0,
    ): array {
        $sql = 'SELECT p.*,'
            . ' (SELECT COUNT(*) FROM {{%variant}} v WHERE v.[[product_id]] = p.[[id]] AND v.[[is_active]] = 1)'
            . ' AS [[variant_count]],'
            . ' COALESCE((SELECT SUM(m.[[quantity_change]]) FROM {{%stock_movement}} m'
            . ' INNER JOIN {{%variant}} v2 ON v2.[[id]] = m.[[variant_id]]'
            . ' WHERE v2.[[product_id]] = p.[[id]] AND v2.[[is_active]] = 1), 0) AS [[on_hand]]'
            . ' FROM {{%product}} p'
            . ' WHERE ' . ($includeInactive ? '1 = 1' : 'p.[[is_active]] = 1')
            . ($search === '' ? '' : ' AND (p.[[name]] LIKE :search OR p.[[sku]] LIKE :search)')
            . ' ORDER BY p.[[name]] ASC'
            . ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        $params = $search === '' ? [] : ['search' => '%' . $search . '%'];
        $rows = $this->db->createCommand($sql, $params)->queryAll();

        $items = [];

        foreach ($rows as $row) {
            $items[] = new ProductListItem(
                product: Product::hydrate($row),
                variantCount: (int) (Row::nullableString($row, 'variant_count') ?? '0'),
                onHand: (int) (Row::nullableString($row, 'on_hand') ?? '0'),
            );
        }

        return $items;
    }

    public function countSearch(string $search = '', bool $includeInactive = false): int
    {
        $query = $this->db->createQuery()->from(self::TABLE);

        if (!$includeInactive) {
            $query->where(['is_active' => 1]);
        }

        if ($search !== '') {
            $query->andWhere(['or', ['like', 'name', $search], ['like', 'sku', $search]]);
        }

        return (int) $query->count();
    }

    /**
     * Active products without an active variant, used by the dashboard.
     *
     * @return list<Product>
     */
    public function withoutVariants(int $limit = 5): array
    {
        $rows = $this->db->createCommand(
            'SELECT p.* FROM {{%product}} p'
            . ' WHERE p.[[is_active]] = 1 AND NOT EXISTS ('
            . ' SELECT 1 FROM {{%variant}} v WHERE v.[[product_id]] = p.[[id]] AND v.[[is_active]] = 1)'
            . ' ORDER BY p.[[name]] ASC LIMIT ' . $limit,
        )->queryAll();

        $products = [];

        foreach ($rows as $row) {
            $products[] = Product::hydrate($row);
        }

        return $products;
    }

    public function insert(Product $product): int
    {
        $this->db->createCommand()->insert(self::TABLE, [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'unit' => $product->unit,
            'qr_token' => $product->qrToken,
            'low_stock_threshold' => $product->lowStockThreshold,
            'is_active' => $product->isActive ? 1 : 0,
            'created_at' => Timestamps::format($product->createdAt),
            'updated_at' => Timestamps::format($product->updatedAt),
        ])->execute();

        return (int) $this->db->getLastInsertId();
    }

    public function update(Product $product): void
    {
        $this->db->createCommand()->update(self::TABLE, [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'unit' => $product->unit,
            'low_stock_threshold' => $product->lowStockThreshold,
            'is_active' => $product->isActive ? 1 : 0,
            'updated_at' => Timestamps::format($product->updatedAt),
        ], ['id' => $product->id])->execute();
    }

    /**
     * @param array<string, int|string> $condition
     */
    private function findOneBy(array $condition): ?Product
    {
        $row = $this->db->createQuery()->select('*')->from(self::TABLE)->where($condition)->one();

        return $row === null ? null : Product::hydrate($row);
    }
}
