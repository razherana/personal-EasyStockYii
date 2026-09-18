<?php

declare(strict_types=1);

namespace App\Products;

use RuntimeException;

use function sprintf;

/**
 * Domain errors raised by the product catalogue services.
 */
final class ProductException extends RuntimeException
{
    public static function skuTaken(string $sku): self
    {
        return new self(sprintf('SKU "%s" is already used by another product.', $sku));
    }

    public static function variantSkuTaken(string $sku): self
    {
        return new self(sprintf('SKU "%s" is already used by another variant.', $sku));
    }

    public static function notFound(int $id): self
    {
        return new self(sprintf('Product #%d was not found.', $id));
    }

    public static function optionTypeNameTaken(string $name): self
    {
        return new self(sprintf('Option type "%s" already exists.', $name));
    }

    public static function optionTypeNotFound(int $id): self
    {
        return new self(sprintf('Option type #%d was not found.', $id));
    }

    public static function optionValueTaken(string $value): self
    {
        return new self(sprintf('Value "%s" already exists for this option type.', $value));
    }

    public static function optionValueInUse(string $value): self
    {
        return new self(sprintf('Value "%s" is used by a variant and cannot be deleted.', $value));
    }

    public static function invalidOption(string $chunk): self
    {
        return new self(sprintf('"%s" is not a valid option, expected "Type=Value".', $chunk));
    }

    public static function optionValueNotFound(string $type, string $value): self
    {
        return new self(sprintf('Value "%s" does not exist for option type "%s".', $value, $type));
    }
}
