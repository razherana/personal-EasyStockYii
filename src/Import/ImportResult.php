<?php

declare(strict_types=1);

namespace App\Import;

use function count;
use function sprintf;

/**
 * Outcome of an import run.
 */
final readonly class ImportResult
{
    /**
     * @param list<string> $errors Messages with the line number they refer to.
     */
    public function __construct(
        public int $rows,
        public int $created,
        public int $updated,
        public int $skipped,
        public array $errors = [],
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0);
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function summary(): string
    {
        return sprintf(
            '%d row(s) read: %d created, %d updated, %d skipped, %d error(s).',
            $this->rows,
            $this->created,
            $this->updated,
            $this->skipped,
            count($this->errors),
        );
    }
}
