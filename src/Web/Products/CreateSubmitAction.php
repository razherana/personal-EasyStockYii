<?php

declare(strict_types=1);

namespace App\Web\Products;

use App\Products\ProductException;
use App\Products\ProductService;
use App\Web\Shared\Flash\FlashMessages;
use App\Web\Shared\Http\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function sprintf;

/**
 * Creates a product from the submitted form.
 */
final readonly class CreateSubmitAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ValidatorInterface $validator,
        private ProductFormView $formView,
        private ProductService $productService,
        private FlashMessages $flashMessages,
        private Redirector $redirector,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(ProductInput $input): ResponseInterface
    {
        $errors = $this->validator->validate($input)->getErrorMessages();

        if (!$input->isLowStockThresholdValid()) {
            $errors[] = 'The low stock threshold must be a whole number of zero or more.';
        }

        if ($errors !== []) {
            return $this->renderForm($input, $errors);
        }

        try {
            $product = $this->productService->create($this->formView->toData($input));
        } catch (ProductException $exception) {
            return $this->renderForm($input, [$exception->getMessage()]);
        }

        $this->flashMessages->success(sprintf('Product "%s" was created.', $product->name));

        return $this->redirector
            ->to($this->urlGenerator->generate('product-view', ['id' => $product->id]))
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
                'isEdit' => false,
            ])
            ->withStatus(Status::UNPROCESSABLE_ENTITY);
    }
}
