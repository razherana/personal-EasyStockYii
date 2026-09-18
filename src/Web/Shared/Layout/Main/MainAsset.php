<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

use App\Web\Shared\Layout\Common\ThemeAsset;
use Yiisoft\Assets\AssetBundle;

final class MainAsset extends AssetBundle
{
    public ?string $basePath = '@assets/main';
    public ?string $baseUrl = '@assetsUrl/main';
    public ?string $sourcePath = '@assetsSource/main';

    public array $css = [
        'layout.css',
        'pages.css',
    ];

    public array $js = [
        'app.js',
    ];

    public array $jsOptions = [
        'defer' => true,
    ];

    public array $depends = [
        ThemeAsset::class,
    ];
}
