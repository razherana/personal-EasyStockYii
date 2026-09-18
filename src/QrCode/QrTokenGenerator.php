<?php

declare(strict_types=1);

namespace App\QrCode;

use App\Products\ProductRepository;
use Yiisoft\Security\Random;

use function rtrim;
use function strtr;

/**
 * Generates the public token a product QR code points to.
 *
 * Tokens are URL safe and unique across products.
 */
final readonly class QrTokenGenerator
{
    private const BYTE_LENGTH = 16;

    public function __construct(
        private ProductRepository $products,
    ) {}

    public function generate(): string
    {
        do {
            $token = $this->randomToken();
        } while ($this->products->existsByQrToken($token));

        return $token;
    }

    private function randomToken(): string
    {
        return rtrim(strtr(Random::string(self::BYTE_LENGTH), '+/', '-_'), '=');
    }
}
