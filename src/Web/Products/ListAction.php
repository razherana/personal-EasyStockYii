<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\ProductRepository;
use App\Web\Shared\Http\Pagination;
use App\Web\Shared\Http\QueryParams;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Product list with search and paging.
 */
final readonly class ListAction
{
    private const PER_PAGE = 20;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ProductRepository $products,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = QueryParams::from($request);
        $search = $query->trimmedString('search');
        $pagination = Pagination::create(
            $this->products->countSearch($search),
            self::PER_PAGE,
            $query->int('page', 1),
        );

        return $this->viewRenderer->render(__DIR__ . '/list', [
            'items' => $this->products->search($search, false, self::PER_PAGE, $pagination->offset()),
            'search' => $search,
            'pagination' => $pagination,
        ]);
    }
}
