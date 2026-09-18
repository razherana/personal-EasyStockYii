<?php

declare(strict_types=1);

namespace App\Web\Options;

use App\Products\OptionRepository;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;

use function sprintf;

/**
 * Adds an option type.
 */
final readonly class CreateTypeAction
{
    public function __construct(
        private ValidatorInterface $validator,
        private OptionRepository $options,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(OptionTypeInput $input): ResponseInterface
    {
        $name = $input->trimmedName();
        $errors = $this->validator->validate($input)->getErrorMessages();

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->flashMessages->error($error);
            }
        } elseif ($this->options->existsTypeName($name)) {
            $this->flashMessages->error(sprintf('Option type "%s" already exists.', $name));
        } else {
            $this->options->createType($name);
            $this->flashMessages->success(sprintf('Option type "%s" was added.', $name));
        }

        return $this->redirector->to($this->urlGenerator->generate('option-list'));
    }
}
