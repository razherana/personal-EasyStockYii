<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\Access\RolePermissions;
use App\User\UserException;
use App\User\UserRole;
use App\User\UserService;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function sprintf;

/**
 * Creates a user account.
 */
final readonly class CreateSubmitAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ValidatorInterface $validator,
        private UserService $userService,
        private RolePermissions $permissions,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(CreateUserInput $input): ResponseInterface
    {
        $role = $input->roleOrNull();
        $errors = $this->validator->validate($input)->getErrorMessages();

        if ($role === null) {
            $errors[] = 'Choose a valid role.';
        }

        if ($errors !== []) {
            return $this->renderForm($input, $errors);
        }

        try {
            $user = $this->userService->create(
                username: $input->trimmedUsername(),
                password: $input->password,
                displayName: $input->displayNameOrDefault(),
                email: $input->emailOrNull(),
                role: $role ?? UserRole::Staff,
                isActive: $input->isActiveChecked(),
            );
        } catch (UserException $exception) {
            return $this->renderForm($input, [$exception->getMessage()]);
        }

        $this->flashMessages->success(sprintf('User "%s" was created.', $user->username));

        return $this->redirector
            ->to($this->urlGenerator->generate('user-list'))
            ->withStatus(Status::FOUND);
    }

    /**
     * @param list<string> $errors
     */
    private function renderForm(CreateUserInput $input, array $errors): ResponseInterface
    {
        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                'errors' => $errors,
                'user' => null,
                'values' => [
                    'displayName' => $input->displayName,
                    'email' => $input->email,
                    'role' => $input->roleOrNull() ?? UserRole::Staff,
                    'isActive' => $input->isActiveChecked(),
                    'username' => $input->username,
                    'password' => $input->password,
                ],
                'rolePermissions' => $this->permissions,
            ])
            ->withStatus(Status::UNPROCESSABLE_ENTITY);
    }
}
