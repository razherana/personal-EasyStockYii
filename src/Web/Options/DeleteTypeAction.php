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
 * Deletes an option type that no variant uses.
 */
final readonly class DeleteTypeAction
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
        $id = (int) $this->currentRoute->getArgument('id');
        $type = $this->options->findType($id);

        if ($type === null) {
            $this->flashMessages->error('Option type not found.');
        } elseif ($this->options->isTypeUsedByVariant($id)) {
            $this->flashMessages->error(
                sprintf('Option type "%s" is used by product variants and cannot be deleted.', $type->name),
            );
        } else {
            $this->options->deleteType($id);
            $this->flashMessages->success(sprintf('Option type "%s" was deleted.', $type->name));
        }

        return $this->redirector->to($this->urlGenerator->generate('option-list'));
    }
}
