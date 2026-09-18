<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Public;

use Yiisoft\Assets\AssetBundle;

final class PublicAsset extends AssetBundle
{
    public ?string $basePath = '@assets/auth';
    public ?string $baseUrl = '@assetsUrl/auth';
    public ?string $sourcePath = '@assetsSource/auth';

    public array $css = [
        'auth.css',
    ];
}
