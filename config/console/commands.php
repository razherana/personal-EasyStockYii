<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'user:create' => Console\CreateUserCommand::class,
    'seed:demo' => Console\SeedDemoCommand::class,
];
