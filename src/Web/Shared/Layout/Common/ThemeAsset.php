<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Common;

use Yiisoft\Assets\AssetBundle;

/**
 * Design tokens, base elements and the third-party stylesheets shared by every layout.
 *
 * Font Awesome (icons) and the web font are loaded from a CDN; `theme.css` holds the local
 * token definitions, so every other stylesheet can rely on them.
 */
final class ThemeAsset extends AssetBundle
{
    public ?string $basePath = '@assets/shared';
    public ?string $baseUrl = '@assetsUrl/shared';
    public ?string $sourcePath = '@assetsSource/shared';

    public array $css = [
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css',
        'theme.css',
        'components.css',
    ];
}
