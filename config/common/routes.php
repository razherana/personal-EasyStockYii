<?php

declare(strict_types=1);

use App\Access\Permission;
use App\Web;
use App\Web\Shared\Middleware\RequirePermission;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * Permission gated routes use `RequirePermission` with the permission name as argument.
 * Guests are redirected to the sign in page by the same middleware.
 *
 * Middleware must be declared before the action.
 *
 * @param string $permission
 */
$guard = static fn(string $permission): array => [
    'class' => RequirePermission::class,
    'withPermission()' => [$permission],
];

return [
    Group::create()
        ->routes(
            // Authentication
            Route::get('/')->name('home')->middleware($guard(Permission::StockView->value))
                ->action(Web\Dashboard\DashboardAction::class),
            Route::get('/login')->name('login')
                ->action(Web\Auth\LoginAction::class),
            Route::post('/login')->name('login-submit')
                ->action(Web\Auth\LoginSubmitAction::class),
            Route::post('/logout')->name('logout')
                ->action(Web\Auth\LogoutAction::class),

            // Products
            Route::get('/products')->name('product-list')->middleware($guard(Permission::ProductView->value))
                ->action(Web\Products\ListAction::class),
            Route::get('/products/create')->name('product-create')->middleware($guard(Permission::ProductManage->value))
                ->action(Web\Products\CreateAction::class),
            Route::post('/products/create')->name('product-create-submit')->middleware($guard(Permission::ProductManage->value))
                ->action(Web\Products\CreateSubmitAction::class),
            Route::get('/products/{id:\d+}')->name('product-view')->middleware($guard(Permission::ProductView->value))
                ->action(Web\Products\ViewAction::class),
            Route::get('/products/{id:\d+}/edit')->name('product-edit')->middleware($guard(Permission::ProductManage->value))
                ->action(Web\Products\EditAction::class),
            Route::post('/products/{id:\d+}/edit')->name('product-edit-submit')->middleware($guard(Permission::ProductManage->value))
                ->action(Web\Products\EditSubmitAction::class),
            Route::post('/products/{id:\d+}/delete')->name('product-delete')->middleware($guard(Permission::ProductManage->value))
                ->action(Web\Products\DeleteAction::class),

            // Option types and values
            Route::get('/options')->name('option-list')->middleware($guard(Permission::OptionManage->value))
                ->action(Web\Options\ListAction::class),
            Route::post('/options')->name('option-create')->middleware($guard(Permission::OptionManage->value))
                ->action(Web\Options\CreateTypeAction::class),
            Route::post('/options/{id:\d+}/delete')->name('option-delete')->middleware($guard(Permission::OptionManage->value))
                ->action(Web\Options\DeleteTypeAction::class),
            Route::post('/options/{id:\d+}/values')->name('option-value-create')->middleware($guard(Permission::OptionManage->value))
                ->action(Web\Options\CreateValueAction::class),
            Route::post('/options/{id:\d+}/values/{valueId:\d+}/delete')->name('option-value-delete')->middleware($guard(Permission::OptionManage->value))
                ->action(Web\Options\DeleteValueAction::class),

            // Stock
            Route::get('/stock')->name('stock-list')->middleware($guard(Permission::StockView->value))
                ->action(Web\Stock\ListAction::class),
            Route::get('/stock/variants/{id:\d+}')->name('stock-variant')->middleware($guard(Permission::StockView->value))
                ->action(Web\Stock\VariantAction::class),
            Route::post('/stock/movements')->name('stock-movement-create')->middleware($guard(Permission::StockOperate->value))
                ->action(Web\Stock\CreateMovementAction::class),

            // Export and import
            Route::get('/export')->name('report-export')->middleware($guard(Permission::Export->value))
                ->action(Web\Reports\ExportAction::class),
            Route::get('/export/{format}')->name('report-export-download')->middleware($guard(Permission::Export->value))
                ->action(Web\Reports\ExportDownloadAction::class),
            Route::get('/import')->name('report-import')->middleware($guard(Permission::Import->value))
                ->action(Web\Reports\ImportAction::class),
            Route::post('/import')->name('report-import-submit')->middleware($guard(Permission::Import->value))
                ->action(Web\Reports\ImportSubmitAction::class),
            Route::get('/import/template')->name('report-import-template')->middleware($guard(Permission::Import->value))
                ->action(Web\Reports\ImportTemplateAction::class),

            // User accounts
            Route::get('/users')->name('user-list')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\ListAction::class),
            Route::get('/users/create')->name('user-create')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\CreateAction::class),
            Route::post('/users/create')->name('user-create-submit')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\CreateSubmitAction::class),
            Route::get('/users/{id:\d+}/edit')->name('user-edit')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\EditAction::class),
            Route::post('/users/{id:\d+}/edit')->name('user-edit-submit')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\EditSubmitAction::class),
            Route::post('/users/{id:\d+}/password')->name('user-password')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\ChangePasswordAction::class),
            Route::post('/users/{id:\d+}/toggle')->name('user-toggle')->middleware($guard(Permission::UserManage->value))
                ->action(Web\Users\ToggleActiveAction::class),

            // Public product summary and QR code (no authentication)
            Route::get('/p/{token}')->name('public-product')
                ->action(Web\PublicProduct\Action::class),
            Route::get('/p/{token}/qr.svg')->name('public-product-qr')
                ->action(Web\PublicProduct\QrCodeAction::class),
        ),
];
