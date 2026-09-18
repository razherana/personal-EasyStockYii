<?php

declare(strict_types=1);

use App\Access\PermissionAccessChecker;
use App\Access\RolePermissions;
use App\User\UserIdentityRepository;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\SessionInterface;
use Yiisoft\User\CurrentUser;

/** @var array $params */

return [
    RolePermissions::class => [
        '__construct()' => [
            'permissions' => $params['permissions'],
        ],
    ],

    AccessCheckerInterface::class => PermissionAccessChecker::class,

    IdentityRepositoryInterface::class => UserIdentityRepository::class,

    CurrentUser::class => [
        'withSession()' => [Reference::to(SessionInterface::class)],
        'withAccessChecker()' => [Reference::to(AccessCheckerInterface::class)],
    ],
];
