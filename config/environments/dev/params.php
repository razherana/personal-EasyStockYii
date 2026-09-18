<?php

declare(strict_types=1);

return [
    'traceLink' => 'phpstorm://open?url=file://{file}&line={line}',
    'database' => [
        'dsn' => 'sqlite:' . dirname(__DIR__, 3) . '/runtime/database/dev.sqlite',
    ],
];
