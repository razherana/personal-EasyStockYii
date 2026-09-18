<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Stock\MovementType;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringNotContainsString;

final class AnalyticsCest
{
    private function seedCatalogue(FunctionalTester $I): void
    {
        $services = $I->services();
        $user = $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $product = $services->productService()->create(new ProductData(
            sku: 'CHAIR',
            name: 'Chair',
            lowStockThreshold: 3,
        ));
        $variantId = $services->variants()->findByProductId($product->id)[0]->id;
        $services->stockService()->record(MovementType::In, $variantId, 6, 'Opening', null, null, $user->id);
    }

    public function analyticsShowsChartsAndSummaries(FunctionalTester $I): void
    {
        $this->seedCatalogue($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/analytics');
        $body = $browser->body($response);

        assertSame(200, $browser->status($response));
        assertStringContainsString('Analytics', $body);
        assertStringContainsString('Movement trend', $body);
        assertStringContainsString('Stock health', $body);
        assertStringContainsString('Top products', $body);
        assertStringContainsString('Chair', $body);
        assertStringContainsString(
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js',
            $body,
        );
        assertStringContainsString('id="movement-trend"', $body);
        assertStringContainsString('id="stock-health"', $body);
    }

    public function guestsAreRedirectedToSignIn(FunctionalTester $I): void
    {
        $browser = $I->browser();
        $response = $browser->get('/analytics');

        assertSame(302, $browser->status($response));
        assertStringContainsString('/login', $browser->header($response, 'Location'));
    }

    public function navigationFollowsTheRolePermissions(FunctionalTester $I): void
    {
        $this->seedCatalogue($I);
        $I->services()->userService()->create('staffer', 'password123', 'Staff', null, UserRole::Staff);

        $admin = $I->loginAsAdmin();
        $adminDashboard = $admin->body($admin->get('/'));

        // The rail lists every section, including the ones only administrators may open.
        assertStringContainsString('Catalogue', $adminDashboard);
        assertStringContainsString('Reports', $adminDashboard);
        assertStringContainsString('Administration', $adminDashboard);

        // Section pages carry their own sidebar links.
        $adminCatalogue = $admin->body($admin->get('/products'));

        assertStringContainsString('Option types', $adminCatalogue);
        assertStringContainsString('New product', $adminCatalogue);

        $staff = $I->browser();
        $staff->login('staffer', 'password123');
        $staffDashboard = $staff->body($staff->get('/'));

        assertStringContainsString('Dashboard', $staffDashboard);
        assertStringContainsString('Analytics', $staffDashboard);
        assertStringContainsString('Low stock', $staffDashboard);
        assertStringNotContainsString('Administration', $staffDashboard);
        assertStringNotContainsString('Reports', $staffDashboard);
        assertStringNotContainsString('New user', $staffDashboard);

        $staffCatalogue = $staff->body($staff->get('/products'));

        assertStringContainsString('All products', $staffCatalogue);
        assertStringNotContainsString('Option types', $staffCatalogue);
        assertStringNotContainsString('New product', $staffCatalogue);
    }

    public function theRailMarksTheActiveSection(FunctionalTester $I): void
    {
        $this->seedCatalogue($I);
        $browser = $I->loginAsAdmin();

        $body = $browser->body($browser->get('/analytics'));

        assertStringContainsString('rail-link is-active', $body);
        assertStringContainsString('aria-current="true"', $body);
        assertStringContainsString('aria-current="page"', $body);
    }
}
