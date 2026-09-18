<?php

declare(strict_types=1);

namespace App\Web\PublicProduct;

use App\Products\ProductRepository;
use App\QrCode\QrCodeGenerator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Returns the QR code of a product as SVG. Public, no authentication.
 */
final readonly class QrCodeAction
{
    public function __construct(
        private ProductRepository $products,
        private QrCodeGenerator $qrCodeGenerator,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $token = (string) $this->currentRoute->getArgument('token');
        $product = $this->products->findByQrToken($token);

        if ($product === null) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        $svg = $this->qrCodeGenerator->generate(
            $this->urlGenerator->generateAbsolute('public-product', ['token' => $product->qrToken]),
        );

        return $this->responseFactory
            ->createResponse(Status::OK)
            ->withHeader('Content-Type', QrCodeGenerator::MIME_TYPE)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->streamFactory->createStream($svg));
    }
}
