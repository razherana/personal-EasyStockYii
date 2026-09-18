<?php

declare(strict_types=1);

namespace App\Web\Shared\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;

/**
 * Creates redirect responses.
 */
final readonly class Redirector
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function to(string $url, int $status = Status::FOUND): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($status)
            ->withHeader('Location', $url);
    }
}
