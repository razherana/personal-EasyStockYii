<?php

declare(strict_types=1);

namespace App\Tests\Support\Http;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFile;
use Psr\Http\Message\ResponseInterface;

use function http_build_query;
use function preg_match;
use function str_contains;
use function strlen;

/**
 * Minimal HTTP client for functional tests.
 *
 * Keeps the session cookie between requests and reuses the CSRF token of the current session,
 * so tests can walk through the application like a browser does.
 */
final class Browser
{
    private const SESSION_COOKIE = 'PHPSESSID';

    private string $sessionId = '';
    private string $csrfToken = '';

    public function __construct(
        private readonly FunctionalTester $tester,
    ) {}

    /**
     * @param array<string, string|int> $data
     * @param array<string, string|int> $query
     */
    public function request(string $method, string $uri, array $data = [], array $query = []): ResponseInterface
    {
        $response = $this->tester->sendRequest($this->buildRequest($method, $uri, $data, $query));

        $this->rememberSession($response);
        $this->rememberCsrfToken($response);

        return $response;
    }

    /**
     * @param array<string, string|int> $data
     * @param array<string, string|int> $query
     */
    private function buildRequest(string $method, string $uri, array $data, array $query = []): ServerRequest
    {
        $target = $uri . ($query === [] ? '' : '?' . http_build_query($query));
        $request = new ServerRequest(method: $method, uri: $target);

        if ($this->sessionId !== '') {
            $request = $request->withCookieParams([self::SESSION_COOKIE => $this->sessionId]);
        }

        if ($data !== []) {
            $request = $request->withParsedBody($data);
        }

        return $request;
    }

    /**
     * @param array<string, string|int> $query
     */
    public function get(string $uri, array $query = []): ResponseInterface
    {
        return $this->request('GET', $uri, [], $query);
    }

    /**
     * @param array<string, string|int> $data
     */
    public function post(string $uri, array $data = []): ResponseInterface
    {
        return $this->request('POST', $uri, ['_csrf' => $this->csrfToken, ...$data]);
    }

    /**
     * Posts a form with a single uploaded file, like a file input does.
     */
    public function postFile(string $uri, string $fileName, string $content, array $data = []): ResponseInterface
    {
        $request = $this->buildRequest('POST', $uri, ['_csrf' => $this->csrfToken, ...$data])
            ->withUploadedFiles([
                'file' => new UploadedFile(
                    new StreamFactory()->createStream($content),
                    strlen($content),
                    UPLOAD_ERR_OK,
                    $fileName,
                    'text/csv',
                ),
            ]);

        $response = $this->tester->sendRequest($request);

        $this->rememberSession($response);
        $this->rememberCsrfToken($response);

        return $response;
    }

    /**
     * Signs in through the login form, the same way a browser would.
     */
    public function login(string $username = 'admin', string $password = 'password123'): ResponseInterface
    {
        $this->get('/login');

        return $this->post('/login', ['username' => $username, 'password' => $password]);
    }

    public function body(ResponseInterface $response): string
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        return (string) $body;
    }

    public function status(ResponseInterface $response): int
    {
        return $response->getStatusCode();
    }

    public function location(ResponseInterface $response): string
    {
        return $response->getHeaderLine('Location');
    }

    public function header(ResponseInterface $response, string $name): string
    {
        return $response->getHeaderLine($name);
    }

    public function csrfToken(): string
    {
        return $this->csrfToken;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Drops the session cookie, which makes the next request an anonymous one.
     */
    public function forgetSession(): void
    {
        $this->sessionId = '';
        $this->csrfToken = '';
    }

    private function rememberSession(ResponseInterface $response): void
    {
        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            if (str_contains($cookie, self::SESSION_COOKIE . '=')) {
                $parts = explode('=', explode(';', $cookie)[0], 2);
                $this->sessionId = $parts[1] ?? '';
            }
        }
    }

    private function rememberCsrfToken(ResponseInterface $response): void
    {
        $body = $this->body($response);

        if (preg_match('/name="_csrf" value="([^"]+)"/', $body, $matches) === 1) {
            $this->csrfToken = $matches[1];
        }
    }
}
