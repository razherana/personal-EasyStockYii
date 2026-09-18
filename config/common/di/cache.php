<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Definitions\Reference;

return [
    CacheInterface::class => ArrayCache::class,

    SchemaCache::class => [
        '__construct()' => [
            'psrCache' => Reference::to(CacheInterface::class),
        ],
    ],
];
