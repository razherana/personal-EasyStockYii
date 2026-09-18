<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Environment;
use App\Tests\Support\Database\DatabaseHelper;
use App\Tests\Support\Http\Browser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

use function dirname;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
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
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * Define custom actions here
     */

    public function sendRequest(ServerRequestInterface $request): ResponseInterface
    {
        $runner = new HttpApplicationRunner(
            rootPath: dirname(__DIR__, 2),
            environment: Environment::appEnv(),
        );

        $response = $runner->runAndGetResponse($request);

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $response;
    }

    /**
     * HTTP client that keeps the session of the application under test.
     */
    public function browser(): Browser
    {
        return new Browser($this);
    }

    /**
     * Services bound to the test database, for setting up fixtures.
     */
    public function services(): Services
    {
        return new Services(DatabaseHelper::createConnection('sqlite:' . DatabaseHelper::testDatabasePath()));
    }

    /**
     * Signs in an administrator and returns the client that keeps the session.
     */
    public function loginAsAdmin(): Browser
    {
        $browser = $this->browser();
        $browser->login('admin', 'password123');

        return $browser;
    }

    public function responseText(ResponseInterface $response): string
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        return (string) $body;
    }
}
