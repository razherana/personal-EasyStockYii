<?php

declare(strict_types=1);

namespace App\Web\Users;

use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Password reset form.
 */
#[FromBody]
final class UserPasswordInput implements RequestInputInterface
{
    #[Required(message: 'Enter a new password.')]
    #[Length(min: 8, lessThanMinMessage: 'The password must be at least {min} characters.')]
    public string $password = '';
}
