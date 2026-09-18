<?php

declare(strict_types=1);

namespace App\Tests\Support\Database;

use App\Migrations\M20260917000001CreateUserTable;
use App\Migrations\M20260917000002CreateProductTables;
use App\Migrations\M20260917000003CreateVariantTables;
use App\Migrations\M20260917000004CreateStockMovementTable;
use App\Shared\Database\SqliteConnectionFactory;
use PDO;
use RuntimeException;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;

use function dirname;
use function glob;
use function is_array;
use function is_file;
use function sprintf;
use function unlink;

/**
 * Database helpers for tests.
 *
 * Unit tests use a single in-memory SQLite connection with the migrations applied once.
 * Functional and web tests use the test database file configured for the `test` environment,
 * which is recreated by `tests/bootstrap.php`.
 */
final class DatabaseHelper
{
    private static ?ConnectionInterface $connection = null;
    private static bool $inMemoryMigrated = false;

    public const TABLES = [
        'stock_movement',
        'variant_option_value',
        'variant',
        'product',
        'option_value',
        'option_type',
        'user',
    ];

    /**
     * @return list<class-string>
     */
    public static function migrationClasses(): array
    {
        return [
            M20260917000001CreateUserTable::class,
            M20260917000002CreateProductTables::class,
            M20260917000003CreateVariantTables::class,
            M20260917000004CreateStockMovementTable::class,
        ];
    }

    /**
     * In-memory database with the schema applied, shared by the unit tests of one process.
     */
    public static function connection(): ConnectionInterface
    {
        self::$connection ??= self::createConnection('sqlite::memory:');

        return self::$connection;
    }

    public static function createConnection(string $dsn): ConnectionInterface
    {
        return (new SqliteConnectionFactory($dsn, new SchemaCache(new ArrayCache())))->create();
    }

    public static function migrate(ConnectionInterface $connection): void
    {
        $builder = new MigrationBuilder($connection, new NullMigrationInformer());

        foreach (self::migrationClasses() as $class) {
            (new $class())->up($builder);
        }
    }

    public static function testDatabasePath(): string
    {
        return dirname(__DIR__, 3) . '/runtime/database/test.sqlite';
    }

    /**
     * Recreates the test database file and applies all migrations. Used by `tests/bootstrap.php`.
     */
    public static function prepareTestDatabase(): void
    {
        $path = self::testDatabasePath();

        foreach (glob($path . '*') ?: [] as $file) {
            unlink($file);
        }

        self::migrate(self::createConnection('sqlite:' . $path));
    }

    /**
     * Empties the tables of the in-memory database, so the next test starts from a clean state.
     */
    public static function resetInMemoryDatabase(): void
    {
        if (!self::$inMemoryMigrated) {
            self::migrate(self::connection());
            self::$inMemoryMigrated = true;
        }

        self::truncate(self::connection());
    }

    /**
     * Empties the tables of the test database file between functional tests.
     */
    public static function resetTestDatabase(): void
    {
        $pdo = new PDO('sqlite:' . self::testDatabasePath());
        $pdo->exec('PRAGMA foreign_keys = OFF');

        foreach (self::TABLES as $table) {
            $pdo->exec(sprintf('DELETE FROM "%s"', $table));
        }
    }

    public static function truncate(ConnectionInterface $connection): void
    {
        $connection->createCommand('PRAGMA foreign_keys = OFF')->execute();

        foreach (self::TABLES as $table) {
            $connection->createCommand(sprintf('DELETE FROM {{%%%s}}', $table))->execute();
        }

        $connection->createCommand('PRAGMA foreign_keys = ON')->execute();
    }

    /**
     * Table occurrences of the schema, used by tests that assert the structure.
     *
     * @return list<string>
     */
    public static function tableNames(ConnectionInterface $connection): array
    {
        /** @var list<array<array-key, mixed>> $rows */
        $rows = $connection
            ->createCommand("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name")
            ->queryAll();

        $names = [];

        foreach ($rows as $row) {
            $name = $row['name'] ?? null;

            if (is_array($name)) {
                throw new RuntimeException('Unexpected table name type.');
            }

            $names[] = (string) $name;
        }

        return $names;
    }

    public static function databaseFileExists(): bool
    {
        return is_file(self::testDatabasePath());
    }
}
