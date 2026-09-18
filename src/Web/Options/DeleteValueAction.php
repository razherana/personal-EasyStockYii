<?php

declare(strict_types=1);

namespace App\Web\Options;

use App\Products\OptionRepository;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;

use function sprintf;

/**
 * Deletes an option value that no variant uses.
 */
final readonly class DeleteValueAction
{
    public function __construct(
        private OptionRepository $options,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $valueId = (int) $this->currentRoute->getArgument('valueId');
        $value = $this->options->findValue($valueId);

        if ($value === null) {
            $this->flashMessages->error('Option value not found.');
        } elseif ($this->options->isValueUsedByVariant($valueId)) {
            $this->flashMessages->error(
                sprintf('"%s" is used by product variants and cannot be deleted.', $value->value),
            );
        } else {
            $this->options->deleteValue($valueId);
            $this->flashMessages->success(sprintf('"%s" was deleted.', $value->value));
        }

        return $this->redirector->to($this->urlGenerator->generate('option-list'));
    }
}
