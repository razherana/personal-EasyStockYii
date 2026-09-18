<?php

declare(strict_types=1);

namespace App\Web\Options;

use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

use function trim;

/**
 * "Add option type" form.
 */
#[FromBody]
final class OptionTypeInput implements RequestInputInterface
{
    #[Required(message: 'Enter an option type name.')]
    #[Length(max: 64, greaterThanMaxMessage: 'The name must be at most {max} characters.')]
    public string $name = '';

    public function trimmedName(): string
    {
        return trim($this->name);
    }
}
