<?php

declare(strict_types=1);

namespace App\Web\Stock;

use App\Stock\MovementType;
use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Required;

use function filter_var;
use function is_float;
use function is_int;
use function str_replace;
use function trim;

use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

/**
 * Stock movement form.
 */
#[FromBody]
final class MovementInput implements RequestInputInterface
{
    #[Required(message: 'Choose a variant.')]
    public string $variantId = '';

    #[Required(message: 'Choose a movement type.')]
    public string $type = 'in';

    #[Required(message: 'Enter a quantity.')]
    public string $quantity = '';

    public string $reference = '';

    public string $note = '';

    public string $unitCost = '';

    public function variantIdOrNull(): ?int
    {
        $parsed = filter_var(trim($this->variantId), FILTER_VALIDATE_INT);

        return is_int($parsed) && $parsed > 0 ? $parsed : null;
    }

    public function typeOrNull(): ?MovementType
    {
        return MovementType::tryFrom(trim($this->type));
    }

    /**
     * Positive quantity for in/out, signed change for adjustments.
     */
    public function quantityOrNull(): ?int
    {
        $parsed = filter_var(trim($this->quantity), FILTER_VALIDATE_INT);

        return is_int($parsed) ? $parsed : null;
    }

    public function unitCostOrNull(): ?float
    {
        $value = str_replace(',', '.', trim($this->unitCost));

        if ($value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_FLOAT);

        return is_float($parsed) && $parsed >= 0 ? $parsed : null;
    }

    public function isUnitCostValid(): bool
    {
        return trim($this->unitCost) === '' || $this->unitCostOrNull() !== null;
    }

    public function referenceOrNull(): ?string
    {
        $reference = trim($this->reference);

        return $reference === '' ? null : $reference;
    }

    public function noteOrNull(): ?string
    {
        $note = trim($this->note);

        return $note === '' ? null : $note;
    }
}
