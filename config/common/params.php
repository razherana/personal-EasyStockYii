<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\User\CurrentUserProvider;
use App\Web\Shared\Flash\FlashMessages;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

return [
    'application' => require __DIR__ . '/application.php',

    'database' => [
        // Override with the APP_DB_DSN environment variable, for example "sqlite:/var/data/easystock.db".
        'dsn' => getenv('APP_DB_DSN') ?: 'sqlite:' . dirname(__DIR__, 2) . '/runtime/database/app.sqlite',
    ],

    /**
     * Permissions granted to each user role. `App\Access\Permission` lists all available permissions.
     */
    'permissions' => [
        'admin' => [
            'product:view',
            'product:manage',
            'option:manage',
            'stock:view',
            'stock:operate',
            'export',
            'import',
            'user:manage',
        ],
        'manager' => [
            'product:view',
            'product:manage',
            'option:manage',
            'stock:view',
            'stock:operate',
            'export',
            'import',
        ],
        'staff' => [
            'product:view',
            'stock:view',
            'stock:operate',
        ],
    ],

    'yiisoft/user' => [
        'authUrl' => '/login',
    ],

    'yiisoft/db-migration' => [
        'newMigrationNamespace' => 'App\Migrations',
        'newMigrationPath' => '',
        'sourceNamespaces' => ['App\Migrations'],
        'sourcePaths' => [dirname(__DIR__, 2) . '/src/Migrations'],
    ],

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'currentUser' => Reference::to(CurrentUserProvider::class),
            'flashMessages' => Reference::to(FlashMessages::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => null,
        'layout' => '@src/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],
];
