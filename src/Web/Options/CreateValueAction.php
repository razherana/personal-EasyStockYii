<?php

declare(strict_types=1);

namespace App\Web\Options;

use App\Products\OptionRepository;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;

use function sprintf;

/**
 * Adds a value to an option type.
 */
final readonly class CreateValueAction
{
    public function __construct(
        private ValidatorInterface $validator,
        private OptionRepository $options,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(OptionValueInput $input): ResponseInterface
    {
        $typeId = (int) $this->currentRoute->getArgument('id');
        $type = $this->options->findType($typeId);
        $value = $input->trimmedValue();
        $errors = $this->validator->validate($input)->getErrorMessages();

        if ($type === null) {
            $this->flashMessages->error('Option type not found.');
        } elseif ($errors !== []) {
            foreach ($errors as $error) {
                $this->flashMessages->error($error);
            }
        } elseif ($this->options->existsValue($typeId, $value)) {
            $this->flashMessages->error(sprintf('"%s" already exists for "%s".', $value, $type->name));
        } else {
            $this->options->createValue($typeId, $value);
            $this->flashMessages->success(sprintf('"%s" was added to "%s".', $value, $type->name));
        }

        return $this->redirector->to($this->urlGenerator->generate('option-list'));
    }
}
