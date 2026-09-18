<?php

declare(strict_types=1);

namespace App\Export;

use function array_map;

/**
 * Tabular data ready to be written by an exporter.
 */
final readonly class ExportDataset
{
    /**
     * @param list<string> $headers
     * @param list<array<int|string, string|int|float|null>> $rows
     */
    public function __construct(
        public string $title,
        public string $fileBaseName,
        public array $headers,
        public array $rows,
    ) {}

    /**
     * @param array<string, string|int|float|null> $row
     */
    public function addRow(array $row): self
    {
        return new self(
            title: $this->title,
            fileBaseName: $this->fileBaseName,
            headers: $this->headers,
            rows: [...$this->rows, $row],
        );
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    public function rowsInHeaderOrder(): array
    {
        $headers = $this->headers;
        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = array_map(
                static fn(string $header): string|int|float|null => $row[$header] ?? null,
                $headers,
            );
        }

        return $rows;
    }
}
