<?php

declare(strict_types=1);

namespace App\Tests\Support\Http;

use Yiisoft\Session\SessionInterface;

use function array_key_exists;
use function bin2hex;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function random_bytes;
use function serialize;
use function unlink;
use function unserialize;

/**
 * File-backed session storage used by the test environment.
 *
 * Functional tests run the application inside the Codeception process, which already sent output, so PHP refuses to
 * start a native session there. This implementation keeps the same behaviour as {@see \Yiisoft\Session\Session}
 * while storing session data in `runtime/sessions`, so in-process (functional) and out-of-process (web) tests can
 * both keep a session between requests.
 */
final class TestSession implements SessionInterface
{
    private const NAME = 'PHPSESSID';

    private ?string $sessionId = null;
    private bool $active = false;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(
        private readonly string $directory = '',
    ) {}

    public static function defaultDirectory(): string
    {
        return dirname(__DIR__, 3) . '/runtime/sessions';
    }

    /**
     * Removes all stored sessions, so every test starts without one.
     */
    public static function clearAll(?string $directory = null): void
    {
        foreach (glob(($directory ?? self::defaultDirectory()) . '/session-*.bin') ?: [] as $file) {
            unlink($file);
        }
    }

    public function get(string $key, $default = null)
    {
        if ($this->sessionId === null) {
            return $default;
        }

        $this->open();

        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->sessionId ??= self::generateId();

        $this->open();
        $this->data[$key] = $value;

        $this->persist();
    }

    public function close(): void
    {
        if (!$this->active) {
            return;
        }

        $this->persist();
        $this->active = false;
    }

    public function open(): void
    {
        if ($this->active) {
            return;
        }

        $this->active = true;
        $this->data = $this->sessionId === null ? [] : $this->read($this->sessionId);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getId(): ?string
    {
        return $this->sessionId;
    }

    public function setId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function regenerateId(): void
    {
        $previousId = $this->sessionId;

        if ($previousId === null) {
            $this->sessionId = self::generateId();
        }

        $this->open();

        if ($previousId !== null && is_file($this->file($previousId))) {
            unlink($this->file($previousId));
        }

        $this->persist();
    }

    public function discard(): void
    {
        if ($this->sessionId !== null) {
            $this->data = $this->read($this->sessionId);
        }

        $this->active = false;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->sessionId === null) {
            return [];
        }

        $this->open();

        return $this->data;
    }

    public function remove(string $key): void
    {
        if ($this->sessionId === null) {
            return;
        }

        $this->open();
        unset($this->data[$key]);

        $this->persist();
    }

    public function has(string $key): bool
    {
        if ($this->sessionId === null) {
            return false;
        }

        $this->open();

        return array_key_exists($key, $this->data);
    }

    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);

        return $value;
    }

    public function clear(): void
    {
        if ($this->sessionId === null) {
            return;
        }

        $this->open();
        $this->data = [];

        $this->persist();
    }

    public function destroy(): void
    {
        if ($this->sessionId !== null && is_file($this->file($this->sessionId))) {
            unlink($this->file($this->sessionId));
        }

        $this->sessionId = null;
        $this->data = [];
        $this->active = false;
    }

    /**
     * @return array{lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string}
     */
    public function getCookieParameters(): array
    {
        return [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function file(string $sessionId): string
    {
        return $this->directory() . '/session-' . $sessionId . '.bin';
    }

    private function directory(): string
    {
        $directory = $this->directory === '' ? self::defaultDirectory() : $this->directory;

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        return $directory;
    }

    private function persist(): void
    {
        if ($this->sessionId === null) {
            return;
        }

        file_put_contents($this->file($this->sessionId), serialize($this->data));
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $sessionId): array
    {
        $file = $this->file($sessionId);

        if (!is_file($file)) {
            return [];
        }

        $contents = file_get_contents($file);

        if ($contents === false || $contents === '') {
            return [];
        }

        /** @var array<string, mixed>|false $data */
        $data = unserialize($contents, ['allowed_classes' => false]);

        return $data === false ? [] : $data;
    }
}
