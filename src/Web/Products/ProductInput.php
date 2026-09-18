<?php

declare(strict_types=1);

namespace App\Web\Products;

use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

use function filter_var;
use function is_int;
use function trim;

use const FILTER_VALIDATE_INT;

/**
 * Product create and edit form.
 */
#[FromBody]
final class ProductInput implements RequestInputInterface
{
    #[Required(message: 'Enter a SKU.')]
    #[Length(max: 64, greaterThanMaxMessage: 'The SKU must be at most {max} characters.')]
    public string $sku = '';

    #[Required(message: 'Enter a product name.')]
    #[Length(max: 190, greaterThanMaxMessage: 'The name must be at most {max} characters.')]
    public string $name = '';

    public string $unit = '';

    public string $description = '';

    public string $lowStockThreshold = '';

    public string $isActive = '';

    /**
     * Option values selected in the form.
     *
     * @var array<int|string, mixed>
     */
    public array $optionValueIds = [];

    public function unitOrDefault(): string
    {
        $unit = trim($this->unit);

        return $unit === '' ? 'piece' : $unit;
    }

    public function lowStockThresholdOrNull(): ?int
    {
        $value = trim($this->lowStockThreshold);

        if ($value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        if (!is_int($parsed) || $parsed < 0) {
            return null;
        }

        return $parsed;
    }

    public function isLowStockThresholdValid(): bool
    {
        $value = trim($this->lowStockThreshold);

        if ($value === '') {
            return true;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($parsed) && $parsed >= 0;
    }

    public function isActiveChecked(): bool
    {
        return $this->isActive === '1';
    }

    public function descriptionOrNull(): ?string
    {
        $description = trim($this->description);

        return $description === '' ? null : $description;
    }

    /**
     * @return list<int>
     */
    public function selectedOptionValueIds(): array
    {
        $ids = [];

        foreach ($this->optionValueIds as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);

            if (is_int($id) && $id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
