<?php

declare(strict_types=1);

namespace App\Web\Shared\Flash;

use Yiisoft\Session\Flash\FlashInterface;

use function is_array;

/**
 * Thin wrapper around flash messages with the two kinds the UI shows.
 */
final readonly class FlashMessages
{
    private const SUCCESS = 'success';
    private const ERROR = 'error';

    public function __construct(
        private FlashInterface $flash,
    ) {}

    public function success(string $message): void
    {
        $this->flash->add(self::SUCCESS, $message);
    }

    public function error(string $message): void
    {
        $this->flash->add(self::ERROR, $message);
    }

    /**
     * Returns all messages and clears them from the session.
     *
     * @return array{success: list<string>, error: list<string>}
     */
    public function pull(): array
    {
        return [
            'success' => $this->messages(self::SUCCESS),
            'error' => $this->messages(self::ERROR),
        ];
    }

    /**
     * @return list<string>
     */
    private function messages(string $key): array
    {
        /** @var mixed $value */
        $value = $this->flash->get($key);

        if ($value === null) {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        $messages = [];

        foreach ($values as $message) {
            $messages[] = (string) $message;
        }

        return $messages;
    }
}
