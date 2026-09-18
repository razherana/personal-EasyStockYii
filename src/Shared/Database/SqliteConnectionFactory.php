<?php

declare(strict_types=1);

namespace App\Shared\Database;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

use function dirname;
use function is_dir;
use function mkdir;
use function str_starts_with;
use function substr;

/**
 * Creates the application database connection (SQLite).
 *
 * The directory of the database file is created on demand, so a fresh checkout works without manual
 * setup. Foreign key constraints are enabled because SQLite disables them by default.
 */
final readonly class SqliteConnectionFactory
{
    public function __construct(
        private string $dsn,
        private SchemaCache $schemaCache,
    ) {}

    public function create(): ConnectionInterface
    {
        $this->ensureDatabaseDirectoryExists();

        $connection = new Connection(new Driver($this->dsn), $this->schemaCache);
        $connection->createCommand('PRAGMA foreign_keys = ON')->execute();

        return $connection;
    }

    private function ensureDatabaseDirectoryExists(): void
    {
        $databaseFile = $this->databaseFile();

        if ($databaseFile === null) {
            return;
        }

        $directory = dirname($databaseFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }
    }

    /**
     * @return string|null Absolute path of the database file, or `null` for in-memory or temporary databases.
     */
    private function databaseFile(): ?string
    {
        if (!str_starts_with($this->dsn, 'sqlite:')) {
            return null;
        }

        $path = substr($this->dsn, 7);

        return $path === '' || str_starts_with($path, ':') ? null : $path;
    }
}
