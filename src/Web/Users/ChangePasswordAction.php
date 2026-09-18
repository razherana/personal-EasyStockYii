<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\User\UserException;
use App\User\UserRepository;
use App\User\UserService;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;

use function sprintf;

/**
 * Sets a new password for a user.
 */
final readonly class ChangePasswordAction
{
    public function __construct(
        private ValidatorInterface $validator,
        private UserRepository $users,
        private UserService $userService,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(UserPasswordInput $input): ResponseInterface
    {
        $id = (int) $this->currentRoute->getArgument('id');
        $user = $this->users->findById($id);
        $errors = $this->validator->validate($input)->getErrorMessages();

        if ($user === null) {
            $this->flashMessages->error('User not found.');
        } elseif ($errors !== []) {
            foreach ($errors as $error) {
                $this->flashMessages->error($error);
            }
        } else {
            try {
                $this->userService->changePassword($id, $input->password);
                $this->flashMessages->success(sprintf('Password of "%s" was changed.', $user->username));
            } catch (UserException $exception) {
                $this->flashMessages->error($exception->getMessage());
            }
        }

        return $this->redirector
            ->to($this->urlGenerator->generate('user-list'))
            ->withStatus(Status::FOUND);
    }
}
