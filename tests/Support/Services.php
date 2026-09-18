<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Products\OptionRepository;
use App\Products\ProductRepository;
use App\Products\ProductService;
use App\Products\VariantGenerator;
use App\Products\VariantRepository;
use App\QrCode\QrTokenGenerator;
use App\Stock\StockRepository;
use App\Stock\StockService;
use App\User\UserRepository;
use App\User\UserService;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Security\PasswordHasher;

/**
 * Builds the application services for tests, without a DI container.
 *
 * Tests show the wiring explicitly and stay readable.
 */
final readonly class Services
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function db(): ConnectionInterface
    {
        return $this->db;
    }

    public function users(): UserRepository
    {
        return new UserRepository($this->db);
    }

    public function userService(?UserRepository $users = null): UserService
    {
        return new UserService($users ?? $this->users(), new PasswordHasher());
    }

    public function options(): OptionRepository
    {
        return new OptionRepository($this->db);
    }

    public function products(): ProductRepository
    {
        return new ProductRepository($this->db);
    }

    public function variants(?OptionRepository $options = null): VariantRepository
    {
        return new VariantRepository($this->db, $options ?? $this->options());
    }

    public function productService(?VariantRepository $variants = null): ProductService
    {
        $products = $this->products();
        $options = $this->options();

        return new ProductService(
            $products,
            $variants ?? new VariantRepository($this->db, $options),
            $options,
            new VariantGenerator(),
            new QrTokenGenerator($products),
            $this->db,
        );
    }

    public function stock(?StockRepository $stock = null): StockRepository
    {
        return $stock ?? new StockRepository($this->db);
    }

    public function stockService(?StockRepository $stock = null): StockService
    {
        return new StockService($stock ?? new StockRepository($this->db), $this->db);
    }
}
