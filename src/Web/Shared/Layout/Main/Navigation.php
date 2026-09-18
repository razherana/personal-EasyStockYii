<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

use App\Access\Permission;
use App\Products\ProductRepository;
use App\Stock\StockRepository;
use App\User\CurrentUserProvider;

/**
 * Builds the two level navigation: icon rail sections and the sidebar of the active section.
 *
 * The rail lists the sections the current user may open. The sidebar shows the groups of the
 * active section, with the dashboard section acting as the full menu.
 */
final class Navigation
{
    /** @var list<NavSection>|null */
    private ?array $sections = null;

    public function __construct(
        private readonly CurrentUserProvider $currentUser,
        private readonly ProductRepository $products,
        private readonly StockRepository $stock,
    ) {}

    /**
     * @return list<NavSection>
     */
    public function sections(): array
    {
        return $this->sections ??= $this->build();
    }

    public function activeSection(string $currentRouteName): ?NavSection
    {
        $sections = $this->sections();

        foreach ($sections as $section) {
            foreach ($section->prefixes as $prefix) {
                if ($prefix !== '' && str_starts_with($currentRouteName, $prefix)) {
                    return $section;
                }
            }
        }

        return $sections[0] ?? null;
    }

    public static function isItemActive(NavItem $item, string $currentRouteName): bool
    {
        return !$item->shortcut && $item->route === $currentRouteName;
    }

    /**
     * @return list<NavSection>
     */
    private function build(): array
    {
        $sections = [];

        if (!$this->currentUser->can(Permission::StockView)) {
            return $sections;
        }

        $sections[] = new NavSection(
            id: 'dashboard',
            label: 'Overview',
            icon: 'fa-gauge-high',
            route: 'home',
            prefixes: ['home', 'dashboard', 'analytics'],
            groups: $this->dashboardGroups(),
        );

        if ($this->currentUser->can(Permission::ProductView)) {
            $sections[] = new NavSection(
                id: 'products',
                label: 'Catalogue',
                icon: 'fa-cube',
                route: 'product-list',
                prefixes: ['product-', 'option-'],
                groups: [
                    new NavGroup('Catalogue', $this->catalogueItems()),
                ],
            );
        }

        $sections[] = new NavSection(
            id: 'stock',
            label: 'Inventory',
            icon: 'fa-warehouse',
            route: 'stock-list',
            prefixes: ['stock-'],
            groups: [
                new NavGroup('Inventory', $this->inventoryItems()),
            ],
        );

        $reports = $this->reportItems();

        if ($reports !== []) {
            $sections[] = new NavSection(
                id: 'reports',
                label: 'Reports',
                icon: 'fa-chart-pie',
                route: $reports[0]->route,
                prefixes: ['report-'],
                groups: [
                    new NavGroup('Reports', $reports),
                ],
            );
        }

        if ($this->currentUser->can(Permission::UserManage)) {
            $sections[] = new NavSection(
                id: 'admin',
                label: 'Administration',
                icon: 'fa-users',
                route: 'user-list',
                prefixes: ['user-'],
                groups: [
                    new NavGroup('Administration', [
                        new NavItem('Users', 'fa-users', 'user-list'),
                        new NavItem('New user', 'fa-user-plus', 'user-create', shortcut: true),
                    ]),
                ],
            );
        }

        return $sections;
    }

    /**
     * @return list<NavGroup>
     */
    private function dashboardGroups(): array
    {
        $browse = [new NavItem('Analytics', 'fa-chart-line', 'analytics')];

        if ($this->currentUser->can(Permission::ProductView)) {
            $browse[] = new NavItem(
                label: 'Products',
                icon: 'fa-cube',
                route: 'product-list',
                badge: $this->products->countActive(),
            );
        }

        $browse[] = new NavItem(
            label: 'Low stock',
            icon: 'fa-triangle-exclamation',
            route: 'stock-list',
            params: ['low' => '1'],
            badge: $this->stock->countLowStockLevels(),
            shortcut: true,
        );

        return [
            new NavGroup('Overview', [
                new NavItem('Dashboard', 'fa-gauge-high', 'home'),
            ]),
            new NavGroup('Browse', $browse),
        ];
    }

    /**
     * @return list<NavItem>
     */
    private function catalogueItems(): array
    {
        $items = [
            new NavItem(
                label: 'All products',
                icon: 'fa-cube',
                route: 'product-list',
                badge: $this->products->countActive(),
            ),
        ];

        if ($this->currentUser->can(Permission::ProductManage)) {
            $items[] = new NavItem('New product', 'fa-circle-plus', 'product-create', shortcut: true);
        }

        if ($this->currentUser->can(Permission::OptionManage)) {
            $items[] = new NavItem('Option types', 'fa-list-check', 'option-list');
        }

        return $items;
    }

    /**
     * @return list<NavItem>
     */
    private function inventoryItems(): array
    {
        return [
            new NavItem('Stock levels', 'fa-warehouse', 'stock-list'),
            new NavItem(
                label: 'Low stock',
                icon: 'fa-triangle-exclamation',
                route: 'stock-list',
                params: ['low' => '1'],
                badge: $this->stock->countLowStockLevels(),
                shortcut: true,
            ),
        ];
    }

    /**
     * @return list<NavItem>
     */
    private function reportItems(): array
    {
        $items = [];

        if ($this->currentUser->can(Permission::Export)) {
            $items[] = new NavItem('Export', 'fa-file-arrow-down', 'report-export');
        }

        if ($this->currentUser->can(Permission::Import)) {
            $items[] = new NavItem('Import', 'fa-file-arrow-up', 'report-import');
        }

        return $items;
    }
}
