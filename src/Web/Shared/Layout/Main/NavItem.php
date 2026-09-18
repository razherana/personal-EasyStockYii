<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

/**
 * A single link inside a sidebar group.
 */
final readonly class NavItem
{
    /**
     * @param array<string, int|string> $params Query parameters appended to the generated URL.
     * @param bool $shortcut Shortcuts point at a filtered view of another page and never become
     *   the active item, so the canonical link stays highlighted.
     */
    public function __construct(
        public string $label,
        public string $icon,
        public string $route,
        public array $params = [],
        public ?int $badge = null,
        public bool $shortcut = false,
    ) {}
}
