<?php

declare(strict_types=1);

namespace App\Web\Options;

use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

use function trim;

/**
 * "Add option value" form.
 */
#[FromBody]
final class OptionValueInput implements RequestInputInterface
{
    #[Required(message: 'Enter a value.')]
    #[Length(max: 64, greaterThanMaxMessage: 'The value must be at most {max} characters.')]
    public string $value = '';

    public function trimmedValue(): string
    {
        return trim($this->value);
    }
}
