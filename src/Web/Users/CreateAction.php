<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\Access\RolePermissions;
use App\User\UserRole;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Shows the empty user form.
 */
final readonly class CreateAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private RolePermissions $permissions,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/form', [
            'errors' => [],
            'user' => null,
            'values' => [
                'displayName' => '',
                'email' => '',
                'role' => UserRole::Staff,
                'isActive' => true,
            ],
            'rolePermissions' => $this->permissions,
        ]);
    }
}
