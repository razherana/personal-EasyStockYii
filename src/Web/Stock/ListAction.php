<?php

declare(strict_types=1);

namespace App\Web\Stock;

use App\Stock\StockRepository;
use App\Web\Shared\Http\Pagination;
use App\Web\Shared\Http\QueryParams;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Stock overview: one row per variant with its level, searchable and filterable.
 */
final readonly class ListAction
{
    private const PER_PAGE = 20;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private StockRepository $stock,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = QueryParams::from($request);
        $search = $query->trimmedString('search');
        $onlyLowStock = $query->bool('low');
        $includeInactive = $query->bool('inactive');
        $pagination = Pagination::create(
            $this->stock->countLevels($search, $onlyLowStock, $includeInactive),
            self::PER_PAGE,
            $query->int('page', 1),
        );

        return $this->viewRenderer->render(__DIR__ . '/list', [
            'levels' => $this->stock->levels(
                $search,
                $onlyLowStock,
                $includeInactive,
                self::PER_PAGE,
                $pagination->offset(),
            ),
            'search' => $search,
            'onlyLowStock' => $onlyLowStock,
            'includeInactive' => $includeInactive,
            'pagination' => $pagination,
        ]);
    }
}
