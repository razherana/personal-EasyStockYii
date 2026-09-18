<?php

declare(strict_types=1);

namespace App\Tests\Unit\Products;

use App\Products\OptionValue;
use App\Products\ProductVariant;
use App\Products\VariantGenerator;
use Codeception\Test\Unit;

use function array_map;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class VariantGeneratorTest extends Unit
{
    private function value(int $id, int $typeId, string $value): OptionValue
    {
        return new OptionValue(id: $id, optionTypeId: $typeId, value: $value, position: 0);
    }

    public function testProductWithoutOptionsGetsOneDefaultVariant(): void
    {
        $variants = (new VariantGenerator())->generate('SCREWS-BOX', []);

        assertCount(1, $variants);
        assertSame('SCREWS-BOX', $variants[0]->sku);
        assertSame([], $variants[0]->optionValueIds);
        assertTrue($variants[0]->isDefault);
    }

    public function testOneValuePerTypeProducesASingleVariant(): void
    {
        $generator = new VariantGenerator();
        $variants = $generator->generate('TSHIRT', [
            $this->value(1, 1, 'XL'),
            $this->value(2, 2, 'Red'),
            $this->value(3, 3, 'With logo'),
        ]);

        assertCount(1, $variants);
        assertSame('TSHIRT-XL-RED-WITH-LOGO', $variants[0]->sku);
        assertSame([1, 2, 3], $variants[0]->optionValueIds);
        assertSame(false, $variants[0]->isDefault);
    }

    public function testCombinationsAreGeneratedForEveryValue(): void
    {
        $generator = new VariantGenerator();
        $variants = $generator->generate('TSHIRT', [
            $this->value(1, 1, 'XL'),
            $this->value(2, 1, 'L'),
            $this->value(3, 2, 'Red'),
            $this->value(4, 2, 'Black'),
        ]);

        assertCount(4, $variants);
        assertSame(
            ['TSHIRT-XL-RED', 'TSHIRT-XL-BLACK', 'TSHIRT-L-RED', 'TSHIRT-L-BLACK'],
            array_map(static fn(ProductVariant|\App\Products\GeneratedVariant $variant): string => $variant->sku, $variants),
        );
    }

    public function testValuesWithAccentsAndSpacesBecomeShortSkus(): void
    {
        $variants = (new VariantGenerator())->generate('TABLE', [
            $this->value(1, 1, '5 ft'),
            $this->value(2, 2, 'Noir mat'),
        ]);

        assertSame('TABLE-5-FT-NOIR-MAT', $variants[0]->sku);
    }

    public function testDuplicateSkusAreMadeUnique(): void
    {
        $variants = (new VariantGenerator())->generate('ITEM', [
            $this->value(1, 1, 'Size'),
            $this->value(2, 1, 'size!'),
        ]);

        assertSame('ITEM-SIZE', $variants[0]->sku);
        assertSame('ITEM-SIZE-2', $variants[1]->sku);
    }
}
