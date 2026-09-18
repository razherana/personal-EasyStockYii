<?php

declare(strict_types=1);

use App\Environment;
use App\Tests\Support\Database\DatabaseHelper;

// Tests always run against the `test` environment and its own SQLite database.
putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';

require_once dirname(__DIR__) . '/src/bootstrap.php';

Environment::prepare();

// This bootstrap also runs on every covered web request: c3.php loads codeception.yml, and loading the Codeception
// configuration executes the bootstrap. Recreating the database there would wipe the data a test has just written,
// so only the command line process (the Codeception run itself) prepares it.
if (PHP_SAPI === 'cli') {
    DatabaseHelper::prepareTestDatabase();
}
