<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\User\UserRepository;
use App\User\UserRole;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * User account list.
 */
final readonly class ListAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $users,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/list', [
            'users' => $this->users->findAll(),
            'roles' => UserRole::cases(),
        ]);
    }
}
