<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\User\CurrentUserProvider;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Shows the sign in form.
 */
final readonly class LoginAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private CurrentUserProvider $currentUser,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(): ResponseInterface
    {
        if (!$this->currentUser->isGuest()) {
            return $this->redirector->to($this->urlGenerator->generate('home'));
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Auth/layout.php')
            ->render(__DIR__ . '/login', ['errors' => []]);
    }
}
