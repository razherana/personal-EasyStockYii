<?php

declare(strict_types=1);

namespace App\QrCode;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Renders QR codes as SVG.
 *
 * SVG keeps the code sharp at any label size and needs no image extension, so the
 * application has no GD dependency.
 */
final readonly class QrCodeGenerator
{
    public const MIME_TYPE = 'image/svg+xml';

    public function __construct(
        private int $size = 320,
        private int $margin = 8,
    ) {}

    public function generate(string $data): string
    {
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $this->size,
            margin: $this->margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return (new SvgWriter())->write($qrCode)->getString();
    }
}
