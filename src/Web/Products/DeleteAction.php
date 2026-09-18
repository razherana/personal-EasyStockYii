<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\ProductException;
use App\Products\ProductService;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;

use function sprintf;

/**
 * Deactivates a product. Stock history stays available.
 */
final readonly class DeleteAction
{
    public function __construct(
        private ProductService $productService,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $id = (int) $this->currentRoute->getArgument('id');

        try {
            $product = $this->productService->deactivate($id);
            $this->flashMessages->success(sprintf('Product "%s" was deactivated.', $product->name));
        } catch (ProductException $exception) {
            $this->flashMessages->error($exception->getMessage());
        }

        return $this->redirector
            ->to($this->urlGenerator->generate('product-list'))
            ->withStatus(Status::FOUND);
    }
}
