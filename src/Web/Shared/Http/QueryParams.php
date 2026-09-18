<?php

declare(strict_types=1);

namespace App\Web\Shared\Http;

use Psr\Http\Message\ServerRequestInterface;

use function filter_var;
use function is_array;
use function is_scalar;
use function is_string;

use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_INT;

/**
 * Typed, defensive reads of query string values.
 */
final readonly class QueryParams
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        private array $values,
    ) {}

    public static function from(ServerRequestInterface $request): self
    {
        /** @var array<string, mixed> $params */
        $params = $request->getQueryParams();

        return new self($params);
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function trimmedString(string $key, string $default = ''): string
    {
        return trim($this->string($key, $default));
    }

    public function int(string $key, int $default): int
    {
        $value = $this->string($key, '');

        if ($value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($parsed) ? $parsed : $default;
    }

    public function bool(string $key): bool
    {
        $value = $this->values[$key] ?? null;

        if (is_array($value)) {
            return false;
        }

        return is_string($value) && filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === true;
    }
}
