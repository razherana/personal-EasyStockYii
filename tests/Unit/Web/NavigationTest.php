<?php

declare(strict_types=1);

namespace App\Tests\Unit\Web;

use App\Web\Shared\Layout\Main\NavItem;
use App\Web\Shared\Layout\Main\Navigation;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

final class NavigationTest extends Unit
{
    public function testItemIsActiveOnItsOwnRoute(): void
    {
        $item = new NavItem('Stock levels', 'fa-warehouse', 'stock-list');

        assertTrue(Navigation::isItemActive($item, 'stock-list'));
        assertFalse(Navigation::isItemActive($item, 'product-list'));
    }

    public function testShortcutsAreNeverActive(): void
    {
        $shortcut = new NavItem('Low stock', 'fa-triangle-exclamation', 'stock-list', ['low' => '1'], null, true);

        assertFalse(Navigation::isItemActive($shortcut, 'stock-list'));
    }
}
