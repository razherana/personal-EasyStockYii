<?php

declare(strict_types=1);

namespace App\Tests\Unit\QrCode;

use App\Products\ProductData;
use App\QrCode\QrCodeGenerator;
use App\QrCode\QrTokenGenerator;
use App\Tests\Support\DatabaseTestCase;

use function PHPUnit\Framework\assertMatchesRegularExpression;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

final class QrCodeTest extends DatabaseTestCase
{
    public function testGeneratorRendersSvgMarkup(): void
    {
        $svg = (new QrCodeGenerator())->generate('https://stock.example.com/p/abc123');

        assertStringContainsString('<svg', $svg);
        assertStringContainsString('</svg>', $svg);
        assertStringContainsString('viewBox', $svg);
    }

    public function testTokenIsUrlSafeAndUnique(): void
    {
        $generator = new QrTokenGenerator($this->services()->products());

        $first = $generator->generate();
        $second = $generator->generate();

        assertMatchesRegularExpression('/^[A-Za-z0-9_-]{16,}$/', $first);
        assertNotSame($first, $second);
    }

    public function testTokenAvoidsTokensAlreadyUsedByAProduct(): void
    {
        $services = $this->services();
        $product = $services->productService()->create(new ProductData(sku: 'CHAIR', name: 'Chair'));
        $generator = new QrTokenGenerator($services->products());

        $token = $generator->generate();

        assertNotSame($product->qrToken, $token);
        assertSame(null, $services->products()->findByQrToken($token));
    }
}
