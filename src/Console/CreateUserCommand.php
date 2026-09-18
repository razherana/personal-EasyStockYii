<?php

declare(strict_types=1);

namespace App\Console;

use App\User\UserException;
use App\User\UserRole;
use App\User\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Creates a user account.
 */
#[AsCommand(
    name: 'user:create',
    description: 'Creates a user account',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserService $userService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'Login name.')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Password (minimum 8 characters).')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Display name.')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email address.')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Role: admin, manager or staff.', UserRole::Admin->value);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $username */
        $username = $input->getOption('username');
        /** @var string|null $password */
        $password = $input->getOption('password');
        /** @var string|null $displayName */
        $displayName = $input->getOption('name');
        /** @var string|null $email */
        $email = $input->getOption('email');
        /** @var string $roleValue */
        $roleValue = $input->getOption('role');

        if ($username === null || $password === null) {
            $io->error('Options --username and --password are required.');

            return ExitCode::USAGE;
        }

        $role = UserRole::tryFrom($roleValue);

        if ($role === null) {
            $io->error(sprintf('Unknown role "%s". Use admin, manager or staff.', $roleValue));

            return ExitCode::USAGE;
        }

        try {
            $user = $this->userService->create(
                username: $username,
                password: $password,
                displayName: $displayName ?? $username,
                email: $email,
                role: $role,
            );
        } catch (UserException $exception) {
            $io->error($exception->getMessage());

            return ExitCode::USAGE;
        }

        $io->success(sprintf('User "%s" created with role "%s".', $user->username, $user->role->value));

        return ExitCode::OK;
    }
}
