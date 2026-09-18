<?php

declare(strict_types=1);

use App\Shared\Database\SqliteConnectionFactory;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    SqliteConnectionFactory::class => [
        '__construct()' => [
            'dsn' => $params['database']['dsn'],
            'schemaCache' => Reference::to(SchemaCache::class),
        ],
    ],

    ConnectionInterface::class => static fn(SqliteConnectionFactory $factory): ConnectionInterface => $factory->create(),
];
