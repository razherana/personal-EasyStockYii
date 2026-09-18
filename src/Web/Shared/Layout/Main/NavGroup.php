<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

/**
 * A labelled group of sidebar links, for example "Catalogue" or "Inventory".
 */
final readonly class NavGroup
{
    /**
     * @param list<NavItem> $items
     */
    public function __construct(
        public string $label,
        public array $items,
    ) {}
}
