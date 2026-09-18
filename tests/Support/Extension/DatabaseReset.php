<?php

declare(strict_types=1);

namespace App\Tests\Support\Extension;

use App\Tests\Support\Database\DatabaseHelper;
use App\Tests\Support\Http\TestSession;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;

/**
 * Empties the test database and the stored test sessions before every test of the suite.
 *
 * Enabled in the functional and web suites through their `*.suite.yml` files.
 */
final class DatabaseReset extends Extension
{
    /**
     * @var array<string, string>
     */
    public static array $events = [
        Events::TEST_BEFORE => 'resetDatabase',
    ];

    public function resetDatabase(TestEvent $event): void
    {
        DatabaseHelper::resetTestDatabase();
        TestSession::clearAll();
    }
}
