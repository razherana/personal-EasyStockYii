<?php

declare(strict_types=1);

namespace App\Products;

use App\QrCode\QrTokenGenerator;
use App\Shared\Database\Timestamps;
use Yiisoft\Db\Connection\ConnectionInterface;

use function trim;

/**
 * Creates and updates products together with their variants.
 *
 * Variants are regenerated from the selected option values: existing variants keep their stock
 * ledger because they are matched by SKU, variants that are no longer selected are deactivated.
 */
final readonly class ProductService
{
    public function __construct(
        private ProductRepository $products,
        private VariantRepository $variants,
        private OptionRepository $options,
        private VariantGenerator $generator,
        private QrTokenGenerator $qrTokens,
        private ConnectionInterface $db,
    ) {}

    /**
     * @throws ProductException
     */
    public function create(ProductData $data): Product
    {
        $sku = trim($data->sku);

        if ($this->products->existsBySku($sku)) {
            throw ProductException::skuTaken($sku);
        }

        return $this->db->transaction(function () use ($data, $sku): Product {
            $now = Timestamps::now();

            $id = $this->products->insert(new Product(
                id: 0,
                sku: $sku,
                name: trim($data->name),
                description: $data->description,
                unit: $this->unit($data->unit),
                qrToken: $this->qrTokens->generate(),
                lowStockThreshold: $data->lowStockThreshold,
                isActive: $data->isActive,
                createdAt: $now,
                updatedAt: $now,
            ));

            $this->syncVariants($id, $sku, $data->optionValueIds);

            return $this->requireProduct($id);
        });
    }

    /**
     * @throws ProductException
     */
    public function update(int $id, ProductData $data): Product
    {
        $product = $this->requireProduct($id);
        $sku = trim($data->sku);

        if ($this->products->existsBySku($sku, $id)) {
            throw ProductException::skuTaken($sku);
        }

        return $this->db->transaction(function () use ($product, $data, $sku): Product {
            $this->products->update(new Product(
                id: $product->id,
                sku: $sku,
                name: trim($data->name),
                description: $data->description,
                unit: $this->unit($data->unit),
                qrToken: $product->qrToken,
                lowStockThreshold: $data->lowStockThreshold,
                isActive: $data->isActive,
                createdAt: $product->createdAt,
                updatedAt: Timestamps::now(),
            ));

            $this->syncVariants($product->id, $sku, $data->optionValueIds);

            return $this->requireProduct($product->id);
        });
    }

    /**
     * Products are deactivated instead of deleted so their stock history stays readable.
     */
    public function deactivate(int $id): Product
    {
        $product = $this->requireProduct($id);

        $this->products->update(new Product(
            id: $product->id,
            sku: $product->sku,
            name: $product->name,
            description: $product->description,
            unit: $product->unit,
            qrToken: $product->qrToken,
            lowStockThreshold: $product->lowStockThreshold,
            isActive: false,
            createdAt: $product->createdAt,
            updatedAt: Timestamps::now(),
        ));

        return $this->requireProduct($id);
    }

    /**
     * @param list<int> $optionValueIds
     */
    private function syncVariants(int $productId, string $productSku, array $optionValueIds): void
    {
        $generated = $this->generator->generate(
            $productSku,
            $this->options->findValuesByIds($optionValueIds),
        );

        $existing = [];

        foreach ($this->variants->findByProductId($productId) as $variant) {
            $existing[$variant->sku] = $variant;
        }

        $now = Timestamps::now();
        $generatedSkus = [];

        foreach ($generated as $generatedVariant) {
            $generatedSkus[$generatedVariant->sku] = true;
            $variant = $existing[$generatedVariant->sku] ?? null;

            if ($variant === null) {
                $this->assertVariantSkuIsFree($generatedVariant->sku);

                $variantId = $this->variants->insert(new ProductVariant(
                    id: 0,
                    productId: $productId,
                    sku: $generatedVariant->sku,
                    isDefault: $generatedVariant->isDefault,
                    isActive: true,
                    createdAt: $now,
                    updatedAt: $now,
                ));

                $this->variants->setOptionValues($variantId, $generatedVariant->optionValueIds);

                continue;
            }

            if (!$variant->isActive || $variant->isDefault !== $generatedVariant->isDefault) {
                $this->variants->update($variant->withActive(true, $now));
            }

            if ($variant->optionValueIds !== $generatedVariant->optionValueIds) {
                $this->variants->setOptionValues($variant->id, $generatedVariant->optionValueIds);
            }
        }

        foreach ($existing as $sku => $variant) {
            if ($variant->isActive && !isset($generatedSkus[$sku])) {
                $this->variants->update($variant->withActive(false, $now));
            }
        }
    }

    private function assertVariantSkuIsFree(string $sku): void
    {
        if ($this->variants->findBySku($sku) !== null) {
            throw ProductException::variantSkuTaken($sku);
        }
    }

    private function requireProduct(int $id): Product
    {
        return $this->products->findById($id) ?? throw ProductException::notFound($id);
    }

    private function unit(string $unit): string
    {
        $unit = trim($unit);

        return $unit === '' ? 'piece' : $unit;
    }
}
