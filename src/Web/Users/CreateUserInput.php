<?php

declare(strict_types=1);

namespace App\Web\Users;

use App\User\UserRole;
use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\RequestInputInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

use function trim;

/**
 * Create user form.
 */
#[FromBody]
final class CreateUserInput implements RequestInputInterface
{
    #[Required(message: 'Enter a username.')]
    #[Length(
        min: 3,
        max: 64,
        lessThanMinMessage: 'The username must be at least {min} characters.',
        greaterThanMaxMessage: 'The username must be at most {max} characters.',
    )]
    public string $username = '';

    #[Required(message: 'Enter a password.')]
    #[Length(min: 8, lessThanMinMessage: 'The password must be at least {min} characters.')]
    public string $password = '';

    #[Length(max: 128, greaterThanMaxMessage: 'The display name must be at most {max} characters.')]
    public string $displayName = '';

    #[Length(max: 190, greaterThanMaxMessage: 'The email must be at most {max} characters.')]
    #[Email(message: 'Enter a valid email address.', skipOnEmpty: true)]
    public string $email = '';

    #[Required(message: 'Choose a role.')]
    public string $role = UserRole::Staff->value;

    public string $isActive = '';

    public function trimmedUsername(): string
    {
        return trim($this->username);
    }

    public function displayNameOrDefault(): string
    {
        $displayName = trim($this->displayName);

        return $displayName === '' ? $this->trimmedUsername() : $displayName;
    }

    public function emailOrNull(): ?string
    {
        $email = trim($this->email);

        return $email === '' ? null : $email;
    }

    public function roleOrNull(): ?UserRole
    {
        return UserRole::tryFrom(trim($this->role));
    }

    public function isActiveChecked(): bool
    {
        return $this->isActive === '1';
    }
}
