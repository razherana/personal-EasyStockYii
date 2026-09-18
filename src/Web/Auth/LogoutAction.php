<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\User\CurrentUserProvider;
use App\Web\Shared\Flash\FlashMessages;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Signs the current user out and returns to the sign in page.
 */
final readonly class LogoutAction
{
    public function __construct(
        private CurrentUserProvider $currentUser,
        private FlashMessages $flashMessages,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->currentUser->logout();
        $this->flashMessages->success('You have been signed out.');

        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('login'));
    }
}
