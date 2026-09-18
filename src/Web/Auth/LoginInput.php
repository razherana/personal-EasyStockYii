<?php

declare(strict_types=1);

namespace App\Web\Auth;

use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Sign in form.
 */
#[FromBody]
final class LoginInput implements RequestInputInterface
{
    #[Required(message: 'Enter your username.')]
    #[Length(max: 64, greaterThanMaxMessage: 'The username is too long.')]
    public string $username = '';

    #[Required(message: 'Enter your password.')]
    #[Length(max: 128, greaterThanMaxMessage: 'The password is too long.')]
    public string $password = '';
}
