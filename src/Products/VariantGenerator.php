<?php

declare(strict_types=1);

namespace App\Products;

use function implode;
use function ksort;
use function mb_strtoupper;
use function preg_replace;
use function strtolower;
use function trim;

/**
 * Builds every combination of the selected option values with a deterministic SKU for each.
 *
 * Example: product "TSHIRT" with Size (XL, L) and Color (Red, Black) produces
 * TSHIRT-XL-RED, TSHIRT-XL-BLACK, TSHIRT-L-RED and TSHIRT-L-BLACK.
 *
 * A product without option values gets a single default variant that reuses the product SKU.
 */
final class VariantGenerator
{
    /**
     * @param list<OptionValue> $optionValues Selected option values; their option type groups them.
     *
     * @return list<GeneratedVariant>
     */
    public function generate(string $productSku, array $optionValues): array
    {
        $groups = $this->groupByOptionType($optionValues);

        if ($groups === []) {
            return [new GeneratedVariant(sku: $productSku, optionValueIds: [], isDefault: true)];
        }

        /** @var list<list<OptionValue>> $combinations */
        $combinations = [[]];

        foreach ($groups as $group) {
            $combinations = $this->combine($combinations, $group);
        }

        $variants = [];
        $usedSkus = [];

        foreach ($combinations as $combination) {
            $sku = $this->buildSku($productSku, $combination, $usedSkus);
            $usedSkus[$sku] = true;

            $variants[] = new GeneratedVariant(
                sku: $sku,
                optionValueIds: $this->valueIds($combination),
                isDefault: false,
            );
        }

        return $variants;
    }

    /**
     * @param list<OptionValue> $optionValues
     *
     * @return array<int, list<OptionValue>> Option type ID to the selected values of that type.
     */
    private function groupByOptionType(array $optionValues): array
    {
        $groups = [];

        foreach ($optionValues as $optionValue) {
            $groups[$optionValue->optionTypeId][] = $optionValue;
        }

        ksort($groups);

        return $groups;
    }

    /**
     * @param list<list<OptionValue>> $combinations
     * @param list<OptionValue> $group
     *
     * @return list<list<OptionValue>>
     */
    private function combine(array $combinations, array $group): array
    {
        $result = [];

        foreach ($combinations as $combination) {
            foreach ($group as $optionValue) {
                $result[] = [...$combination, $optionValue];
            }
        }

        return $result;
    }

    /**
     * @param list<OptionValue> $combination
     * @param array<string, bool> $usedSkus SKUs generated so far, so the result stays unique.
     */
    private function buildSku(string $productSku, array $combination, array $usedSkus): string
    {
        $parts = [];

        foreach ($combination as $optionValue) {
            $parts[] = $this->slug($optionValue->value);
        }

        $suffix = $parts === [] ? '' : '-' . implode('-', $parts);
        $sku = mb_strtoupper($productSku . $suffix);
        $candidate = $sku;
        $counter = 1;

        while (isset($usedSkus[$candidate])) {
            $counter++;
            $candidate = $sku . '-' . $counter;
        }

        return $candidate;
    }

    /**
     * @param list<OptionValue> $combination
     *
     * @return list<int>
     */
    private function valueIds(array $combination): array
    {
        $ids = [];

        foreach ($combination as $optionValue) {
            $ids[] = $optionValue->id;
        }

        return $ids;
    }

    private function slug(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($value))), '-') ?: 'x';
    }
}
