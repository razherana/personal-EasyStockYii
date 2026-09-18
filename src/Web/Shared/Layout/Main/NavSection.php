<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

/**
 * One entry of the icon rail together with the sidebar navigation shown while it is active.
 */
final readonly class NavSection
{
    /**
     * @param list<string> $prefixes Route name prefixes that mark this section as active.
     * @param list<NavGroup> $groups Sidebar groups shown for this section.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $icon,
        public string $route,
        public array $prefixes,
        public array $groups,
    ) {}
}
