<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\Access\RolePermissions;
use App\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Shows the user form with the current values.
 */
final readonly class EditAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $users,
        private RolePermissions $permissions,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->users->findById((int) $this->currentRoute->getArgument('id'));

        if ($user === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        return $this->viewRenderer->render(__DIR__ . '/form', [
            'errors' => [],
            'user' => $user,
            'values' => [
                'displayName' => $user->displayName,
                'email' => $user->email ?? '',
                'role' => $user->role,
                'isActive' => $user->isActive,
            ],
            'rolePermissions' => $this->permissions,
        ]);
    }
}
