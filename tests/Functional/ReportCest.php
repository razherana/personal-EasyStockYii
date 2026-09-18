<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Products\ProductData;
use App\Tests\Support\FunctionalTester;
use App\User\UserRole;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertStringStartsWith;

final class ReportCest
{
    private function seedAdminAndProduct(FunctionalTester $I, string $sku = 'CHAIR', string $name = 'Chair'): void
    {
        $services = $I->services();
        $services->userService()->create('admin', 'password123', 'Admin', null, UserRole::Admin);
        $services->productService()->create(new ProductData(sku: $sku, name: $name));
    }

    public function exportPageListsDataSetsAndFormats(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();

        $body = $browser->body($browser->get('/export'));

        assertStringContainsString('Stock levels', $body);
        assertStringContainsString('Excel (XLSX)', $body);
        assertStringContainsString('PDF', $body);
    }

    public function exportCsvDownloadsStockLevels(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/export/csv', ['dataset' => 'stock']);
        $body = $browser->body($response);

        assertSame(200, $browser->status($response));
        assertStringContainsString('text/csv', $browser->header($response, 'Content-Type'));
        assertStringContainsString('attachment; filename="stock-', $browser->header($response, 'Content-Disposition'));
        assertStringStartsWith("\xEF\xBB\xBF", $body);
        assertStringContainsString('product_sku,product,variant_sku', $body);
        assertStringContainsString('CHAIR', $body);
    }

    public function exportXlsxDownloadsAWorkbook(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/export/xlsx', ['dataset' => 'products']);
        $body = $browser->body($response);

        assertSame(200, $browser->status($response));
        assertStringContainsString('spreadsheetml.sheet', $browser->header($response, 'Content-Type'));
        assertStringStartsWith('PK', $body);
    }

    public function exportPdfDownloadsAReport(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/export/pdf', ['dataset' => 'movements']);

        assertSame(200, $browser->status($response));
        assertStringContainsString('application/pdf', $browser->header($response, 'Content-Type'));
        assertStringStartsWith('%PDF-', $browser->body($response));
    }

    public function unknownExportFormatReturnsNotFound(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();

        assertSame(404, $browser->status($browser->get('/export/ods')));
    }

    public function importCsvCreatesProducts(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();
        $csv = "sku,name,unit,low_stock_threshold,active,options,quantity\n"
            . "TABLE-5FT,Table 5ft black,piece,2,1,Color=Black,4\n"
            . ",Missing sku,,,1,,\n";

        $response = $browser->postFile('/import', 'products.csv', $csv);
        $body = $browser->body($response);

        assertSame(200, $browser->status($response));
        assertStringContainsString('2 row(s) read: 1 created, 0 updated, 0 skipped, 1 error(s).', $body);
        assertStringContainsString('the "sku" column is required', $body);

        $services = $I->services();
        $product = $services->products()->findBySku('TABLE-5FT');

        assertSame('Table 5ft black', $product?->name);
        assertSame(4, $services->stock()->totalOnHand());
    }

    public function importTemplateDownloadsCsv(FunctionalTester $I): void
    {
        $this->seedAdminAndProduct($I);
        $browser = $I->loginAsAdmin();

        $response = $browser->get('/import/template', ['format' => 'csv']);

        assertSame(200, $browser->status($response));
        assertStringContainsString(
            'attachment; filename="import-template-',
            $browser->header($response, 'Content-Disposition'),
        );
        assertStringContainsString('TSHIRT-XL-RED', $browser->body($response));
    }

    public function staffCannotExportOrImport(FunctionalTester $I): void
    {
        $I->services()->userService()->create('staffer', 'password123', 'Staffer', null, UserRole::Staff);

        $browser = $I->browser();
        $browser->login('staffer', 'password123');

        assertSame(403, $browser->status($browser->get('/export')));
        assertSame(403, $browser->status($browser->get('/import')));
        assertSame(403, $browser->status($browser->get('/export/csv')));
    }

    public function guestsAreRedirectedToTheLoginPage(FunctionalTester $I): void
    {
        $browser = $I->browser();

        $response = $browser->get('/export/csv');

        assertSame(302, $browser->status($response));
        assertStringContainsString('/login', $browser->location($response));
    }
}
