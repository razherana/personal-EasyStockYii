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
 * Edit user form.
 */
#[FromBody]
final class EditUserInput implements RequestInputInterface
{
    #[Length(max: 128, greaterThanMaxMessage: 'The display name must be at most {max} characters.')]
    public string $displayName = '';

    #[Length(max: 190, greaterThanMaxMessage: 'The email must be at most {max} characters.')]
    #[Email(message: 'Enter a valid email address.', skipOnEmpty: true)]
    public string $email = '';

    #[Required(message: 'Choose a role.')]
    public string $role = UserRole::Staff->value;

    public string $isActive = '';

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
