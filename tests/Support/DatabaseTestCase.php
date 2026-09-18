<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Tests\Support\Database\DatabaseHelper;
use Codeception\Test\Unit;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Base class for unit tests that need the database.
 *
 * Each test starts with an empty schema: the helper keeps one in-memory SQLite connection for
 * the whole process and truncates the tables before every test.
 */
abstract class DatabaseTestCase extends Unit
{
    protected function _before(): void
    {
        DatabaseHelper::resetInMemoryDatabase();
    }

    protected function db(): ConnectionInterface
    {
        return DatabaseHelper::connection();
    }

    protected function services(): Services
    {
        return new Services($this->db());
    }
}
