<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\Access\RolePermissions;
use App\User\CurrentUserProvider;
use App\User\UserException;
use App\User\UserRepository;
use App\User\UserRole;
use App\User\UserService;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function sprintf;

/**
 * Updates a user account.
 */
final readonly class EditSubmitAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ValidatorInterface $validator,
        private UserRepository $users,
        private UserService $userService,
        private RolePermissions $permissions,
        private CurrentUserProvider $currentUser,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(EditUserInput $input): ResponseInterface
    {
        $id = (int) $this->currentRoute->getArgument('id');
        $user = $this->users->findById($id);
        $actingUserId = $this->currentUser->id() ?? 0;

        if ($user === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        $role = $input->roleOrNull();
        $errors = $this->validator->validate($input)->getErrorMessages();

        if ($role === null) {
            $errors[] = 'Choose a valid role.';
        }

        if ($errors !== []) {
            return $this->renderForm($user, $input, $errors);
        }

        try {
            $updated = $this->userService->update(
                id: $id,
                displayName: $input->displayName,
                email: $input->emailOrNull(),
                role: $role ?? UserRole::Staff,
                isActive: $input->isActiveChecked(),
                actingUserId: $actingUserId,
            );
        } catch (UserException $exception) {
            return $this->renderForm($user, $input, [$exception->getMessage()]);
        }

        $this->flashMessages->success(sprintf('User "%s" was updated.', $updated->username));

        return $this->redirector
            ->to($this->urlGenerator->generate('user-list'))
            ->withStatus(Status::FOUND);
    }

    /**
     * @param list<string> $errors
     */
    private function renderForm(
        \App\User\User $user,
        EditUserInput $input,
        array $errors,
    ): ResponseInterface {
        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                'errors' => $errors,
                'user' => $user,
                'values' => [
                    'displayName' => $input->displayName,
                    'email' => $input->email,
                    'role' => $input->roleOrNull() ?? UserRole::Staff,
                    'isActive' => $input->isActiveChecked(),
                ],
                'rolePermissions' => $this->permissions,
            ])
            ->withStatus(Status::UNPROCESSABLE_ENTITY);
    }
}
