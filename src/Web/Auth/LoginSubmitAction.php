<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\User\CurrentUserProvider;
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
 * Handles the sign in form submission.
 */
final readonly class LoginSubmitAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ValidatorInterface $validator,
        private UserService $userService,
        private CurrentUserProvider $currentUser,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(LoginInput $input): ResponseInterface
    {
        $result = $this->validator->validate($input);

        if (!$result->isValid()) {
            return $this->renderForm($result->getErrorMessages());
        }

        $user = $this->userService->authenticate($input->username, $input->password);

        if ($user === null) {
            return $this->renderForm(['Invalid username or password.']);
        }

        $this->currentUser->login($user);
        $this->flashMessages->success(sprintf('Signed in as %s.', $user->name()));

        return $this->redirector->to($this->urlGenerator->generate('home'));
    }

    /**
     * @param list<string> $errors
     */
    private function renderForm(array $errors): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Auth/layout.php')
            ->render(__DIR__ . '/login', ['errors' => $errors])
            ->withStatus(Status::UNPROCESSABLE_ENTITY);
    }
}
