<?php

declare(strict_types=1);

use App\Tests\Support\Http\TestSession;
use Yiisoft\Session\SessionInterface;

/**
 * Replaces the native PHP session with a file-backed one.
 *
 * Functional tests run the application inside the Codeception process where output was already sent, and PHP refuses
 * to start a native session after that. {@see TestSession} keeps the session cookie flow (`SessionMiddleware`) intact
 * while storing data on disk, so both the functional and the web suite can keep a session between requests.
 */
return [
    SessionInterface::class => TestSession::class,
];
