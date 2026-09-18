<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\OptionRepository;
use App\Products\OptionType;
use App\Products\OptionValue;
use App\Products\Product;
use App\Products\ProductData;
use App\Products\VariantRepository;

/**
 * Builds the data the product form needs: option types with their values and the selected ones.
 */
final readonly class ProductFormView
{
    public function __construct(
        private OptionRepository $options,
        private VariantRepository $variants,
    ) {}

    /**
     * @return array{
     *     input: ProductInput,
     *     types: list<OptionType>,
     *     values: array<int, list<OptionValue>>,
     *     selected: list<int>
     * }
     */
    public function forProduct(?Product $product = null): array
    {
        $input = new ProductInput();

        if ($product !== null) {
            $input->sku = $product->sku;
            $input->name = $product->name;
            $input->unit = $product->unit;
            $input->description = $product->description ?? '';
            $input->lowStockThreshold = $product->lowStockThreshold === null
                ? ''
                : (string) $product->lowStockThreshold;
            $input->isActive = $product->isActive ? '1' : '';
            $input->optionValueIds = $this->variants->optionValueIdsForProduct($product->id);
        }

        return [
            'input' => $input,
            'types' => $this->options->findTypes(),
            'values' => $this->options->findValuesGroupedByType(),
            'selected' => $input->selectedOptionValueIds(),
        ];
    }

    /**
     * Data used to render the form again after a failed submission.
     *
     * @return array{
     *     input: ProductInput,
     *     types: list<OptionType>,
     *     values: array<int, list<OptionValue>>,
     *     selected: list<int>
     * }
     */
    public function fromInput(ProductInput $input): array
    {
        return [
            'input' => $input,
            'types' => $this->options->findTypes(),
            'values' => $this->options->findValuesGroupedByType(),
            'selected' => $input->selectedOptionValueIds(),
        ];
    }

    public function toData(ProductInput $input): ProductData
    {
        return new ProductData(
            sku: $input->sku,
            name: $input->name,
            unit: $input->unitOrDefault(),
            description: $input->descriptionOrNull(),
            lowStockThreshold: $input->lowStockThresholdOrNull(),
            isActive: $input->isActiveChecked(),
            optionValueIds: $input->selectedOptionValueIds(),
        );
    }
}
