<?php

declare(strict_types=1);

namespace App\Web\Shared\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Rejects requests when the current user lacks the required permission.
 *
 * Guests are sent to the sign in page, signed in users without the permission get a 403 page.
 * Configured per route with a DI array definition:
 * `['class' => RequirePermission::class, 'withPermission()' => ['stock:operate']]`.
 */
final readonly class RequirePermission implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private string $permission = '',
    ) {}

    public function withPermission(string $permission): self
    {
        return new self(
            $this->currentUser,
            $this->viewRenderer,
            $this->responseFactory,
            $this->urlGenerator,
            $permission,
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->currentUser->isGuest()) {
            return $this->responseFactory
                ->createResponse(Status::FOUND)
                ->withHeader('Location', $this->urlGenerator->generate('login'));
        }

        if ($this->permission === '' || $this->currentUser->can($this->permission)) {
            return $handler->handle($request);
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/forbidden', ['permission' => $this->permission])
            ->withStatus(Status::FORBIDDEN);
    }
}
