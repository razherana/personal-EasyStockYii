<?php

declare(strict_types=1);

namespace App\Web\Reports;

use App\Import\ImportException;
use App\Import\ImportResult;
use App\Import\ProductImporter;
use App\Import\RowReaderFactory;
use App\User\CurrentUserProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function count;
use function end;
use function explode;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Handles the import upload: stores the file, imports the rows and shows the report.
 */
final readonly class ImportSubmitAction
{
    private const MAX_FILE_SIZE = 5_000_000;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private RowReaderFactory $rowReaderFactory,
        private ProductImporter $productImporter,
        private CurrentUserProvider $currentUser,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $this->currentUser->id();

        if ($userId === null) {
            return $this->renderResult(new ImportResult(0, 0, 0, 0, ['Your session expired. Sign in again.']))
                ->withStatus(Status::UNAUTHORIZED);
        }

        $files = $request->getUploadedFiles();
        $file = $files['file'] ?? null;

        if (!$file instanceof UploadedFileInterface) {
            return $this->renderResult(new ImportResult(0, 0, 0, 0, ['Choose a CSV or XLSX file to import.']))
                ->withStatus(Status::UNPROCESSABLE_ENTITY);
        }

        return $this->renderResult($this->importFile($file, $userId));
    }

    private function importFile(UploadedFileInterface $file, int $userId): ImportResult
    {
        $fileName = (string) $file->getClientFilename();

        if ($file->getSize() !== null && $file->getSize() > self::MAX_FILE_SIZE) {
            return new ImportResult(0, 0, 0, 0, ['The file is larger than 5 MB.']);
        }

        $extension = $this->extensionOf($fileName);
        $tempFile = tempnam(sys_get_temp_dir(), 'easystock-import');

        if ($tempFile === false) {
            return new ImportResult(0, 0, 0, 0, ['The uploaded file could not be stored.']);
        }

        $tempPath = $tempFile . '.' . $extension;

        if (file_put_contents($tempPath, (string) $file->getStream()) === false) {
            return new ImportResult(0, 0, 0, 0, ['The uploaded file could not be stored.']);
        }

        try {
            $reader = $this->rowReaderFactory->readerFor($fileName);

            return $this->productImporter->import($reader->rows($tempPath), $userId);
        } catch (ImportException $exception) {
            return new ImportResult(0, 0, 0, 0, [$exception->getMessage()]);
        } finally {
            unlink($tempPath);
        }
    }

    private function extensionOf(string $fileName): string
    {
        $parts = explode('.', $fileName);

        return count($parts) > 1 ? end($parts) : 'csv';
    }

    private function renderResult(ImportResult $result): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/import', ['result' => $result]);
    }
}
