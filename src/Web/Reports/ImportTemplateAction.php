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

use function basename;

/**
 * Downloads the empty import template as CSV or XLSX.
 */
final readonly class ImportTemplateAction
{
    public function __construct(
        private ExportService $exportService,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $format = ExportFormat::tryFrom(QueryParams::from($request)->string('format')) ?? ExportFormat::Csv;

        if ($format === ExportFormat::Pdf) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        $dataset = $this->exportService->dataset(ExportDatasetName::ImportTemplate);
        $filePath = $this->exportService->exportToFile($dataset, $format);
        $fileName = basename($this->exportService->suggestedFileName($dataset, $format));

        return $this->responseFactory
            ->createResponse(Status::OK)
            ->withHeader('Content-Type', $format->mimeType())
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->streamFactory->createStreamFromFile($filePath, 'r'));
    }
}
