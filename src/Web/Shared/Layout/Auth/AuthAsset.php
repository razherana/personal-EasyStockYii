<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Auth;

use App\Web\Shared\Layout\Common\ThemeAsset;
use Yiisoft\Assets\AssetBundle;

final class AuthAsset extends AssetBundle
{
    public ?string $basePath = '@assets/auth';
    public ?string $baseUrl = '@assetsUrl/auth';
    public ?string $sourcePath = '@assetsSource/auth';

    public array $css = [
        'auth.css',
    ];

    public array $js = [
        'auth.js',
    ];

    public array $jsOptions = [
        'defer' => true,
    ];

    public array $depends = [
        ThemeAsset::class,
    ];
}
