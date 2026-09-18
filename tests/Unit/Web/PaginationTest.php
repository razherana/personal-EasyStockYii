<?php

declare(strict_types=1);

namespace App\Tests\Unit\Web;

use App\Web\Shared\Http\Pagination;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class PaginationTest extends Unit
{
    public function testOffsetAndPageCount(): void
    {
        $pagination = Pagination::create(total: 45, perPage: 20, page: 3);

        assertSame(40, $pagination->offset());
        assertSame(3, $pagination->pageCount());
        assertFalse($pagination->hasNext());
        assertTrue($pagination->hasPrevious());
        assertSame(2, $pagination->previousPage());
    }

    public function testPageIsClampedBetweenOneAndPageCount(): void
    {
        assertSame(1, Pagination::create(10, 20, 0)->page);
        assertSame(1, Pagination::create(10, 20, -5)->page);
        assertSame(2, Pagination::create(30, 20, 99)->page);
        assertSame(1, Pagination::create(0, 20, 1)->pageCount());
        assertTrue(Pagination::create(0, 20, 1)->isEmpty());
    }

    public function testNextPage(): void
    {
        $pagination = Pagination::create(total: 30, perPage: 20, page: 1);

        assertSame(2, $pagination->nextPage());
        assertTrue($pagination->hasNext());
        assertFalse($pagination->hasPrevious());
    }
}
