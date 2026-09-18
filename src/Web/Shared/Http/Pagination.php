<?php

declare(strict_types=1);

namespace App\Web\Shared\Http;

use function ceil;
use function max;
use function min;

/**
 * Simple pagination value object used by list pages.
 */
final readonly class Pagination
{
    private function __construct(
        public int $total,
        public int $perPage,
        public int $page,
    ) {}

    public static function create(int $total, int $perPage, int $page): self
    {
        $perPage = max(1, $perPage);
        $pageCount = max(1, (int) ceil($total / $perPage));

        return new self($total, $perPage, min(max(1, $page), $pageCount));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function pageCount(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pageCount();
    }

    public function previousPage(): int
    {
        return max(1, $this->page - 1);
    }

    public function nextPage(): int
    {
        return min($this->pageCount(), $this->page + 1);
    }

    public function isEmpty(): bool
    {
        return $this->total === 0;
    }
}
