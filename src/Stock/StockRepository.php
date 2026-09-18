<?php

declare(strict_types=1);

namespace App\Stock;

use App\Shared\Database\Row;
use App\Shared\Database\Timestamps;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

use function max;
use function sprintf;

/**
 * Stores and reads the stock movement ledger.
 *
 * Levels are always computed from the ledger, so no denormalised quantity can drift.
 */
final readonly class StockRepository
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function insert(StockMovement $movement): int
    {
        $this->db->createCommand()->insert('stock_movement', [
            'variant_id' => $movement->variantId,
            'type' => $movement->type->value,
            'quantity_change' => $movement->quantityChange,
            'unit_cost' => $movement->unitCost,
            'reference' => $movement->reference,
            'note' => $movement->note,
            'created_by' => $movement->createdBy,
            'created_at' => Timestamps::format($movement->createdAt),
        ])->execute();

        return (int) $this->db->getLastInsertId();
    }

    /**
     * Latest movements with their variant and product, for the dashboard and reports.
     *
     * @return list<MovementListItem>
     */
    public function latestMovementItems(int $limit = 10): array
    {
        $rows = $this->db->createCommand(
            'SELECT m.*, u.[[display_name]] AS [[created_by_name]], u.[[username]] AS [[created_by_username]],'
            . ' v.[[sku]] AS [[variant_sku]], p.[[id]] AS [[product_id]], p.[[name]] AS [[product_name]]'
            . ' FROM {{%stock_movement}} m'
            . ' LEFT JOIN {{%user}} u ON u.[[id]] = m.[[created_by]]'
            . ' INNER JOIN {{%variant}} v ON v.[[id]] = m.[[variant_id]]'
            . ' INNER JOIN {{%product}} p ON p.[[id]] = v.[[product_id]]'
            . ' ORDER BY m.[[created_at]] DESC, m.[[id]] DESC'
            . ' LIMIT :limit',
            ['limit' => $limit],
        )->queryAll();

        $items = [];

        foreach ($rows as $row) {
            $items[] = MovementListItem::hydrate(self::withCreatorName($row));
        }

        return $items;
    }

    public function levelForVariant(int $variantId): int
    {
        return (int) $this->db
            ->createCommand(
                'SELECT COALESCE(SUM([[quantity_change]]), 0) FROM {{%stock_movement}} WHERE [[variant_id]] = :variantId',
                ['variantId' => $variantId],
            )
            ->queryScalar();
    }

    public function totalOnHand(): int
    {
        return (int) $this->db
            ->createCommand('SELECT COALESCE(SUM([[quantity_change]]), 0) FROM {{%stock_movement}}')
            ->queryScalar();
    }

    /**
     * @return list<StockMovement>
     */
    public function movementsForVariant(int $variantId, int $limit = 50): array
    {
        $rows = $this->db->createCommand(
            'SELECT m.*, u.[[display_name]] AS [[created_by_name]], u.[[username]] AS [[created_by_username]]'
            . ' FROM {{%stock_movement}} m'
            . ' LEFT JOIN {{%user}} u ON u.[[id]] = m.[[created_by]]'
            . ' WHERE m.[[variant_id]] = :variantId'
            . ' ORDER BY m.[[created_at]] DESC, m.[[id]] DESC'
            . ' LIMIT :limit',
            ['variantId' => $variantId, 'limit' => $limit],
        )->queryAll();

        $movements = [];

        foreach ($rows as $row) {
            $movements[] = StockMovement::hydrate(self::withCreatorName($row));
        }

        return $movements;
    }

    /**
     * Latest movements across all variants.
     *
     * @return list<StockMovement>
     */
    public function latestMovements(int $limit = 10): array
    {
        $rows = $this->db->createCommand(
            'SELECT m.*, u.[[display_name]] AS [[created_by_name]], u.[[username]] AS [[created_by_username]]'
            . ' FROM {{%stock_movement}} m'
            . ' LEFT JOIN {{%user}} u ON u.[[id]] = m.[[created_by]]'
            . ' ORDER BY m.[[created_at]] DESC, m.[[id]] DESC'
            . ' LIMIT :limit',
            ['limit' => $limit],
        )->queryAll();

        $movements = [];

        foreach ($rows as $row) {
            $movements[] = StockMovement::hydrate(self::withCreatorName($row));
        }

        return $movements;
    }

    /**
     * Stock levels for a variant of a single product.
     *
     * @return list<StockLevel>
     */
    public function levelsForProduct(int $productId): array
    {
        /** @var list<StockLevel> */
        return $this->queryLevels('v.[[product_id]] = :productId', '', ['productId' => $productId]);
    }

    /**
     * Stock levels of a single variant.
     */
    public function levelOfVariant(int $variantId): ?StockLevel
    {
        $levels = $this->queryLevels('v.[[id]] = :variantId', '', ['variantId' => $variantId]);

        return $levels[0] ?? null;
    }

    /**
     * Paged stock levels with an optional product search and low stock filter.
     *
     * @return list<StockLevel>
     */
    public function levels(
        string $search = '',
        bool $onlyLowStock = false,
        bool $includeInactive = false,
        int $limit = 25,
        int $offset = 0,
    ): array {
        [$where, $having, $params] = $this->levelFilters($search, $onlyLowStock, $includeInactive);

        return $this->queryLevels($where, $having, $params, $limit, $offset);
    }

    public function countLevels(string $search = '', bool $onlyLowStock = false, bool $includeInactive = false): int
    {
        [$where, $having, $params] = $this->levelFilters($search, $onlyLowStock, $includeInactive);

        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM ('
            . ' SELECT v.[[id]] AS [[variant_id]]'
            . ' FROM {{%variant}} v'
            . ' INNER JOIN {{%product}} p ON p.[[id]] = v.[[product_id]]'
            . ' LEFT JOIN {{%stock_movement}} m ON m.[[variant_id]] = v.[[id]]'
            . ' WHERE ' . $where
            . ' GROUP BY v.[[id]], p.[[low_stock_threshold]]'
            . $having
            . ')',
            $params,
        )->queryScalar();
    }

    public function countLowStockLevels(): int
    {
        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM ('
            . ' SELECT v.[[id]], p.[[low_stock_threshold]] AS [[threshold]],'
            . ' COALESCE(SUM(m.[[quantity_change]]), 0) AS [[on_hand]]'
            . ' FROM {{%variant}} v'
            . ' INNER JOIN {{%product}} p ON p.[[id]] = v.[[product_id]]'
            . ' LEFT JOIN {{%stock_movement}} m ON m.[[variant_id]] = v.[[id]]'
            . ' WHERE p.[[low_stock_threshold]] IS NOT NULL AND p.[[is_active]] = 1 AND v.[[is_active]] = 1'
            . ' GROUP BY v.[[id]], p.[[low_stock_threshold]]'
            . ' HAVING COALESCE(SUM(m.[[quantity_change]]), 0) <= p.[[low_stock_threshold]]'
            . ')',
        )->queryScalar();
    }

    /**
     * Units booked in, out and adjusted per day, oldest first.
     *
     * Days without movements are not returned; callers fill the gaps.
     *
     * @return list<array{day: string, incoming: int, outgoing: int, adjustment: int}>
     */
    public function dailyMovementTotals(int $days = 14): array
    {
        $rows = $this->db->createCommand(
            'SELECT date([[created_at]]) AS [[day]],'
            . ' COALESCE(SUM(CASE WHEN [[type]] = :typeIn THEN [[quantity_change]] ELSE 0 END), 0) AS [[incoming]],'
            . ' COALESCE(SUM(CASE WHEN [[type]] = :typeOut THEN -[[quantity_change]] ELSE 0 END), 0) AS [[outgoing]],'
            . ' COALESCE(SUM(CASE WHEN [[type]] = :typeAdjustment THEN [[quantity_change]] ELSE 0 END), 0)'
            . ' AS [[adjustment]]'
            . ' FROM {{%stock_movement}}'
            . ' WHERE [[created_at]] >= :from'
            . ' GROUP BY date([[created_at]])'
            . ' ORDER BY [[day]]',
            [
                'typeIn' => MovementType::In->value,
                'typeOut' => MovementType::Out->value,
                'typeAdjustment' => MovementType::Adjustment->value,
                'from' => $this->dayStart()->modify(sprintf('-%d days', max(1, $days)))->format('Y-m-d H:i:s'),
            ],
        )->queryAll();

        $totals = [];

        foreach ($rows as $row) {
            $totals[] = [
                'day' => Row::string($row, 'day'),
                'incoming' => Row::int($row, 'incoming'),
                'outgoing' => Row::int($row, 'outgoing'),
                'adjustment' => Row::int($row, 'adjustment'),
            ];
        }

        return $totals;
    }

    /**
     * How many active variants are out of stock, low on stock or healthy.
     *
     * @return array{out: int, low: int, healthy: int}
     */
    public function levelStatusCounts(): array
    {
        $rows = $this->db->createCommand(
            'SELECT'
            . ' CASE WHEN COALESCE(SUM(m.[[quantity_change]]), 0) <= 0 THEN 0'
            . ' WHEN p.[[low_stock_threshold]] IS NOT NULL'
            . ' AND COALESCE(SUM(m.[[quantity_change]]), 0) <= p.[[low_stock_threshold]] THEN 1'
            . ' ELSE 2 END AS [[status]]'
            . ' FROM {{%variant}} v'
            . ' INNER JOIN {{%product}} p ON p.[[id]] = v.[[product_id]]'
            . ' LEFT JOIN {{%stock_movement}} m ON m.[[variant_id]] = v.[[id]]'
            . ' WHERE p.[[is_active]] = 1 AND v.[[is_active]] = 1'
            . ' GROUP BY v.[[id]], p.[[low_stock_threshold]]',
        )->queryAll();

        $counts = ['out' => 0, 'low' => 0, 'healthy' => 0];

        foreach ($rows as $row) {
            $status = Row::int($row, 'status');

            if ($status === 0) {
                $counts['out']++;
            } elseif ($status === 1) {
                $counts['low']++;
            } else {
                $counts['healthy']++;
            }
        }

        return $counts;
    }

    /**
     * Active products with the most units in stock.
     *
     * @return list<array{name: string, onHand: int}>
     */
    public function topProductsByOnHand(int $limit = 8): array
    {
        $rows = $this->db->createCommand(
            'SELECT p.[[name]] AS [[product_name]], COALESCE(SUM(m.[[quantity_change]]), 0) AS [[on_hand]]'
            . ' FROM {{%product}} p'
            . ' INNER JOIN {{%variant}} v ON v.[[product_id]] = p.[[id]]'
            . ' LEFT JOIN {{%stock_movement}} m ON m.[[variant_id]] = v.[[id]]'
            . ' WHERE p.[[is_active]] = 1 AND v.[[is_active]] = 1'
            . ' GROUP BY p.[[id]], p.[[name]]'
            . ' HAVING COALESCE(SUM(m.[[quantity_change]]), 0) > 0'
            . ' ORDER BY [[on_hand]] DESC, p.[[name]]'
            . ' LIMIT :limit',
            ['limit' => $limit],
        )->queryAll();

        $products = [];

        foreach ($rows as $row) {
            $products[] = [
                'name' => Row::string($row, 'product_name'),
                'onHand' => Row::int($row, 'on_hand'),
            ];
        }

        return $products;
    }

    private function dayStart(): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTime(0, 0);
    }

    /**
     * @param array<non-empty-string, int|string> $params
     *
     * @return list<StockLevel>
     */
    private function queryLevels(
        string $where,
        string $having,
        array $params,
        ?int $limit = null,
        int $offset = 0,
    ): array {
        $sql = 'SELECT v.[[id]] AS [[variant_id]], v.[[sku]] AS [[variant_sku]],'
            . ' v.[[is_default]] AS [[is_default_variant]], v.[[is_active]] AS [[is_active_variant]],'
            . ' p.[[id]] AS [[product_id]], p.[[name]] AS [[product_name]], p.[[sku]] AS [[product_sku]],'
            . ' p.[[unit]] AS [[product_unit]], p.[[is_active]] AS [[is_active_product]],'
            . ' p.[[low_stock_threshold]] AS [[low_stock_threshold]],'
            . ' COALESCE(SUM(m.[[quantity_change]]), 0) AS [[on_hand]],'
            . ' MAX(m.[[created_at]]) AS [[last_movement_at]]'
            . ' FROM {{%variant}} v'
            . ' INNER JOIN {{%product}} p ON p.[[id]] = v.[[product_id]]'
            . ' LEFT JOIN {{%stock_movement}} m ON m.[[variant_id]] = v.[[id]]'
            . ' WHERE ' . $where
            . ' GROUP BY v.[[id]], v.[[sku]], v.[[is_default]], v.[[is_active]], p.[[id]], p.[[name]],'
            . ' p.[[sku]], p.[[unit]], p.[[is_active]], p.[[low_stock_threshold]]'
            . $having
            . ' ORDER BY p.[[name]], v.[[sku]]';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        $rows = $this->db->createCommand($sql, $params)->queryAll();

        $levels = [];

        foreach ($rows as $row) {
            $levels[] = StockLevel::hydrate($row);
        }

        return $levels;
    }

    /**
     * @return array{0: string, 1: string, 2: array<non-empty-string, int|string>} WHERE clause, HAVING clause, parameters.
     */
    private function levelFilters(string $search, bool $onlyLowStock, bool $includeInactive): array
    {
        $where = [];
        $having = [];
        $params = [];

        if (!$includeInactive) {
            $where[] = 'p.[[is_active]] = 1 AND v.[[is_active]] = 1';
        }

        if ($search !== '') {
            $where[] = '(p.[[name]] LIKE :search OR p.[[sku]] LIKE :search OR v.[[sku]] LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($onlyLowStock) {
            $having[] = 'p.[[low_stock_threshold]] IS NOT NULL'
                . ' AND COALESCE(SUM(m.[[quantity_change]]), 0) <= p.[[low_stock_threshold]]';
        }

        return [
            $where === [] ? '1 = 1' : implode(' AND ', $where),
            $having === [] ? '' : ' HAVING ' . implode(' AND ', $having),
            $params,
        ];
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    private static function withCreatorName(array $row): array
    {
        $displayName = Row::nullableString($row, 'created_by_name');
        $username = Row::nullableString($row, 'created_by_username');

        $row['created_by_name'] = $displayName === null || $displayName === '' ? $username : $displayName;

        return $row;
    }
}
