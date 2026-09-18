<?php

declare(strict_types=1);

namespace App\Products;

use Yiisoft\Db\Connection\ConnectionInterface;

use function array_map;
use function implode;

use const SORT_ASC;

/**
 * Reads and writes option types and their values.
 */
final readonly class OptionRepository
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @return list<OptionType>
     */
    public function findTypes(): array
    {
        $rows = $this->db
            ->createQuery()
            ->select('*')
            ->from('option_type')
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $types = [];

        foreach ($rows as $row) {
            $types[] = OptionType::hydrate($row);
        }

        return $types;
    }

    public function findType(int $id): ?OptionType
    {
        $row = $this->db->createQuery()->select('*')->from('option_type')->where(['id' => $id])->one();

        return $row === null ? null : OptionType::hydrate($row);
    }

    public function existsTypeName(string $name): bool
    {
        return $this->db->createQuery()->from('option_type')->where(['name' => $name])->exists();
    }

    /**
     * @return list<OptionValue>
     */
    public function findValues(?int $optionTypeId = null): array
    {
        $query = $this->db
            ->createQuery()
            ->select('*')
            ->from('option_value')
            ->orderBy(['option_type_id' => SORT_ASC, 'value' => SORT_ASC]);

        if ($optionTypeId !== null) {
            $query->where(['option_type_id' => $optionTypeId]);
        }

        $rows = $query->all();

        $values = [];

        foreach ($rows as $row) {
            $values[] = OptionValue::hydrate($row);
        }

        return $values;
    }

    public function findValue(int $id): ?OptionValue
    {
        $row = $this->db->createQuery()->select('*')->from('option_value')->where(['id' => $id])->one();

        return $row === null ? null : OptionValue::hydrate($row);
    }

    /**
     * Option values grouped by option type, for the product form.
     *
     * @return array<int, list<OptionValue>>
     */
    public function findValuesGroupedByType(): array
    {
        $grouped = [];

        foreach ($this->findValues() as $value) {
            $grouped[$value->optionTypeId][] = $value;
        }

        return $grouped;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<OptionValue>
     */
    public function findValuesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->db
            ->createQuery()
            ->select('*')
            ->from('option_value')
            ->where(['in', 'id', $ids])
            ->orderBy(['option_type_id' => SORT_ASC, 'value' => SORT_ASC])
            ->all();

        $values = [];

        foreach ($rows as $row) {
            $values[] = OptionValue::hydrate($row);
        }

        return $values;
    }

    /**
     * Option values used by the given variants, keyed by variant ID.
     *
     * @param list<int> $variantIds
     *
     * @return array<int, list<OptionValue>>
     */
    public function valuesByVariant(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        $rows = $this->db->createCommand(
            'SELECT vov.[[variant_id]] AS [[variant_id]], ov.*'
            . ' FROM {{%variant_option_value}} vov'
            . ' INNER JOIN {{%option_value}} ov ON ov.[[id]] = vov.[[option_value_id]]'
            . ' WHERE vov.[[variant_id]] IN (' . self::idList($variantIds) . ')'
            . ' ORDER BY ov.[[option_type_id]], ov.[[value]]',
        )->queryAll();

        $grouped = [];

        foreach ($rows as $row) {
            /** @var mixed $variantId */
            $variantId = $row['variant_id'] ?? 0;
            $grouped[(int) $variantId][] = OptionValue::hydrate($row);
        }

        return $grouped;
    }

    /**
     * Option type names keyed by type ID, used to label variants.
     *
     * @return array<int, string>
     */
    public function typeNames(): array
    {
        $names = [];

        foreach ($this->findTypes() as $type) {
            $names[$type->id] = $type->name;
        }

        return $names;
    }

    public function createType(string $name): int
    {
        $this->db->createCommand()->insert('option_type', [
            'name' => $name,
            'position' => 0,
        ])->execute();

        return (int) $this->db->getLastInsertId();
    }

    public function createValue(int $optionTypeId, string $value): int
    {
        $this->db->createCommand()->insert('option_value', [
            'option_type_id' => $optionTypeId,
            'value' => $value,
            'position' => 0,
        ])->execute();

        return (int) $this->db->getLastInsertId();
    }

    public function deleteType(int $id): void
    {
        $this->db->createCommand()->delete('option_type', ['id' => $id])->execute();
    }

    public function deleteValue(int $id): void
    {
        $this->db->createCommand()->delete('option_value', ['id' => $id])->execute();
    }

    public function existsValue(int $optionTypeId, string $value): bool
    {
        return $this->db
            ->createQuery()
            ->from('option_value')
            ->where(['option_type_id' => $optionTypeId, 'value' => $value])
            ->exists();
    }

    public function isValueUsedByVariant(int $valueId): bool
    {
        return $this->db
            ->createQuery()
            ->from('variant_option_value')
            ->where(['option_value_id' => $valueId])
            ->exists();
    }

    public function isTypeUsedByVariant(int $typeId): bool
    {
        return (int) $this->db->createCommand(
            'SELECT COUNT(*) FROM {{%variant_option_value}} vov'
            . ' INNER JOIN {{%option_value}} ov ON ov.[[id]] = vov.[[option_value_id]]'
            . ' WHERE ov.[[option_type_id]] = :typeId',
            ['typeId' => $typeId],
        )->queryScalar() > 0;
    }

    public function countTypes(): int
    {
        return (int) $this->db->createQuery()->from('option_type')->count();
    }

    public function countValues(): int
    {
        return (int) $this->db->createQuery()->from('option_value')->count();
    }

    /**
     * @param list<int> $ids
     */
    private static function idList(array $ids): string
    {
        return implode(',', array_map(static fn(int $id): int => $id, $ids));
    }
}
