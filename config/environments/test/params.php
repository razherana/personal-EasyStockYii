<?php

declare(strict_types=1);

return [
    'database' => [
        'dsn' => 'sqlite:' . dirname(__DIR__, 3) . '/runtime/database/test.sqlite',
    ],
];
