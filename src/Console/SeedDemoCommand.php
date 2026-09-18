<?php

declare(strict_types=1);

namespace App\Console;

use App\Products\OptionRepository;
use App\Products\ProductData;
use App\Products\ProductRepository;
use App\Products\ProductService;
use App\Products\VariantRepository;
use App\Stock\MovementType;
use App\Stock\StockService;
use App\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

use function sprintf;

/**
 * Creates demo option types, products and stock movements. Safe to run twice.
 */
#[AsCommand(
    name: 'seed:demo',
    description: 'Creates demo option types, products, variants and stock movements',
)]
final class SeedDemoCommand extends Command
{
    public function __construct(
        private readonly OptionRepository $options,
        private readonly ProductRepository $products,
        private readonly VariantRepository $variants,
        private readonly ProductService $productService,
        private readonly StockService $stockService,
        private readonly UserRepository $users,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $this->firstUserId();

        if ($userId === null) {
            $io->error('Create a user first: ./yii user:create --username=admin --password=change-me');

            return ExitCode::USAGE;
        }

        $size = $this->optionType('Size', ['XL', 'L', '5ft']);
        $color = $this->optionType('Color', ['Red', 'Black']);
        $logo = $this->optionType('Logo', ['With logo', 'Without logo']);

        $values = $this->options->findValues();
        $byName = [];

        foreach ($values as $value) {
            $byName[$value->value] = $value->id;
        }

        $tshirt = $this->product('TSHIRT', 'T-Shirt XL red with logo', 'piece', 10, [
            $byName['XL'],
            $byName['Red'],
            $byName['With logo'],
        ]);

        $table = $this->product('TABLE', 'Table 5ft black', 'piece', 3, [
            $byName['5ft'],
            $byName['Black'],
        ]);

        $this->product('SCREWS-BOX', 'Screws box 500 pieces', 'piece', 5, []);

        if ($tshirt !== null) {
            $this->stockIn($tshirt, 24, 'Opening stock', $userId);
        }

        if ($table !== null) {
            $this->stockIn($table, 4, 'Opening stock', $userId);
        }

        $io->success(sprintf(
            'Demo data ready: %d option types, %d products, %d variants.',
            count([$size, $color, $logo]),
            $this->products->countAll(),
            $this->variants->countAll(),
        ));

        return ExitCode::OK;
    }

    /**
     * @param list<string> $values
     */
    private function optionType(string $name, array $values): int
    {
        if ($this->options->existsTypeName($name)) {
            $type = null;

            foreach ($this->options->findTypes() as $candidate) {
                if ($candidate->name === $name) {
                    $type = $candidate;
                }
            }

            $typeId = $type?->id ?? 0;
        } else {
            $typeId = $this->options->createType($name);
        }

        foreach ($values as $value) {
            if (!$this->options->existsValue($typeId, $value)) {
                $this->options->createValue($typeId, $value);
            }
        }

        return $typeId;
    }

    /**
     * Creates the product when the SKU is new.
     *
     * @param list<int> $optionValueIds
     *
     * @return int|null ID of the new product, or null when it already existed.
     */
    private function product(
        string $sku,
        string $name,
        string $unit,
        int $lowStockThreshold,
        array $optionValueIds,
    ): ?int {
        if ($this->products->existsBySku($sku)) {
            return null;
        }

        $product = $this->productService->create(new ProductData(
            sku: $sku,
            name: $name,
            unit: $unit,
            description: sprintf('Demo product %s.', $name),
            lowStockThreshold: $lowStockThreshold,
            optionValueIds: $optionValueIds,
        ));

        return $product->id;
    }

    private function stockIn(int $productId, int $quantity, string $reference, int $userId): void
    {
        $variants = $this->variants->findByProductId($productId, true);

        foreach ($variants as $variant) {
            $this->stockService->record(
                type: MovementType::In,
                variantId: $variant->id,
                quantity: $quantity,
                reference: $reference,
                note: null,
                unitCost: null,
                userId: $userId,
            );
        }
    }

    private function firstUserId(): ?int
    {
        foreach ($this->users->findAll() as $user) {
            return $user->id;
        }

        return null;
    }
}
