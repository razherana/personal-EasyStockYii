<?php

declare(strict_types=1);

namespace App\Web\Stock;

use App\Stock\StockException;
use App\Stock\StockService;
use App\User\CurrentUserProvider;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;

use function sprintf;

/**
 * Records a stock movement.
 */
final readonly class CreateMovementAction
{
    public function __construct(
        private ValidatorInterface $validator,
        private StockService $stockService,
        private CurrentUserProvider $currentUser,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(MovementInput $input): ResponseInterface
    {
        $variantId = $input->variantIdOrNull();
        $type = $input->typeOrNull();
        $quantity = $input->quantityOrNull();
        $userId = $this->currentUser->id();

        if ($userId === null) {
            $this->flashMessages->error('Your session expired. Sign in again.');

            return $this->redirector->to($this->urlGenerator->generate('login'));
        }

        $errors = $this->validator->validate($input)->getErrorMessages();

        if (!$input->isUnitCostValid()) {
            $errors[] = 'The unit cost must be a number of zero or more.';
        }

        if ($variantId === null) {
            $errors[] = 'Choose a variant.';
        }

        if ($type === null) {
            $errors[] = 'Choose a valid movement type.';
        }

        if ($quantity === null) {
            $errors[] = 'Enter the quantity as a whole number.';
        }

        if ($errors !== [] || $variantId === null || $type === null || $quantity === null) {
            foreach ($errors as $error) {
                $this->flashMessages->error($error);
            }

            return $this->redirectToVariant($variantId);
        }

        try {
            $this->stockService->record(
                type: $type,
                variantId: $variantId,
                quantity: $quantity,
                reference: $input->referenceOrNull(),
                note: $input->noteOrNull(),
                unitCost: $input->unitCostOrNull(),
                userId: $userId,
            );
        } catch (StockException $exception) {
            $this->flashMessages->error($exception->getMessage());

            return $this->redirectToVariant($variantId);
        }

        $this->flashMessages->success(sprintf(
            '%s recorded: %+d.',
            $type->label(),
            $type->toQuantityChange($quantity),
        ));

        return $this->redirectToVariant($variantId);
    }

    private function redirectToVariant(?int $variantId): ResponseInterface
    {
        $url = $variantId === null
            ? $this->urlGenerator->generate('stock-list')
            : $this->urlGenerator->generate('stock-variant', ['id' => $variantId]);

        return $this->redirector->to($url)->withStatus(Status::FOUND);
    }
}
