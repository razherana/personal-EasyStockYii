<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\User\CurrentUserProvider;
use App\User\UserException;
use App\User\UserRepository;
use App\User\UserService;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;

use function sprintf;

/**
 * Activates or deactivates a user account.
 */
final readonly class ToggleActiveAction
{
    public function __construct(
        private UserRepository $users,
        private UserService $userService,
        private CurrentUserProvider $currentUser,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $id = (int) $this->currentRoute->getArgument('id');
        $user = $this->users->findById($id);
        $actingUserId = $this->currentUser->id() ?? 0;

        if ($user === null) {
            $this->flashMessages->error('User not found.');
        } else {
            try {
                $this->userService->setActive($id, !$user->isActive, $actingUserId);
                $this->flashMessages->success(sprintf(
                    'User "%s" was %s.',
                    $user->username,
                    $user->isActive ? 'deactivated' : 'activated',
                ));
            } catch (UserException $exception) {
                $this->flashMessages->error($exception->getMessage());
            }
        }

        return $this->redirector
            ->to($this->urlGenerator->generate('user-list'))
            ->withStatus(Status::FOUND);
    }
}
