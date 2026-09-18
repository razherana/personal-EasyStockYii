<?php

declare(strict_types=1);

namespace App\Web\Reports;

use App\Export\ExportDatasetName;
use App\Export\ExportFormat;
use App\Export\ExportService;
use App\Web\Shared\Http\QueryParams;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

use function basename;

/**
 * Streams a generated export file to the browser.
 */
final readonly class ExportDownloadAction
{
    public function __construct(
        private ExportService $exportService,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $format = ExportFormat::tryFrom((string) $this->currentRoute->getArgument('format'));

        if ($format === null) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        $dataset = ExportDatasetName::tryFrom(QueryParams::from($request)->string('dataset'))
            ?? ExportDatasetName::Stock;

        $data = $this->exportService->dataset($dataset);
        $filePath = $this->exportService->exportToFile($data, $format);

        return $this->responseFactory
            ->createResponse(Status::OK)
            ->withHeader('Content-Type', $format->mimeType())
            ->withHeader(
                'Content-Disposition',
                'attachment; filename="' . basename($this->exportService->suggestedFileName($data, $format)) . '"',
            )
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->streamFactory->createStreamFromFile($filePath, 'r'));
    }
}
