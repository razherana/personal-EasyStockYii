<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\ProductException;
use App\Products\ProductRepository;
use App\Products\ProductService;
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
 * Updates a product from the submitted form.
 */
final readonly class EditSubmitAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ValidatorInterface $validator,
        private ProductFormView $formView,
        private ProductRepository $products,
        private ProductService $productService,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(ProductInput $input): ResponseInterface
    {
        $id = (int) $this->currentRoute->getArgument('id');
        $product = $this->products->findById($id);

        if ($product === null) {
            return $this->viewRenderer
                ->render(__DIR__ . '/not_found')
                ->withStatus(Status::NOT_FOUND);
        }

        $errors = $this->validator->validate($input)->getErrorMessages();

        if (!$input->isLowStockThresholdValid()) {
            $errors[] = 'The low stock threshold must be a whole number of zero or more.';
        }

        if ($errors !== []) {
            return $this->renderForm($input, $errors);
        }

        try {
            $updated = $this->productService->update($id, $this->formView->toData($input));
        } catch (ProductException $exception) {
            return $this->renderForm($input, [$exception->getMessage()]);
        }

        $this->flashMessages->success(sprintf('Product "%s" was updated.', $updated->name));

        return $this->redirector
            ->to($this->urlGenerator->generate('product-view', ['id' => $updated->id]))
            ->withStatus(Status::FOUND);
    }

    /**
     * @param list<string> $errors
     */
    private function renderForm(ProductInput $input, array $errors): ResponseInterface
    {
        return $this->viewRenderer
            ->render(__DIR__ . '/form', [
                ...$this->formView->fromInput($input),
                'errors' => $errors,
                'isEdit' => true,
            ])
            ->withStatus(Status::UNPROCESSABLE_ENTITY);
    }
}
