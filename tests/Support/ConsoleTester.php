<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Tests\Support\Database\DatabaseHelper;

use function dirname;
use function sprintf;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class ConsoleTester extends \Codeception\Actor
{
    use _generated\ConsoleTesterActions;

    /**
     * Define custom actions here
     */

    /**
     * Runs `./yii` in the test environment, the way a developer would run it.
     */
    public function runApp(?string $parameters = null, bool $expectSuccess = true): void
    {
        $this->runShellCommand(
            sprintf(
                'APP_ENV=test %s/yii%s --no-interaction',
                dirname(__DIR__, 2),
                $parameters === null ? '' : ' ' . $parameters,
            ),
            $expectSuccess,
        );
    }

    /**
     * Services bound to the test database, to check what a command did.
     */
    public function services(): Services
    {
        return new Services(DatabaseHelper::createConnection('sqlite:' . DatabaseHelper::testDatabasePath()));
    }
}
