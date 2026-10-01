<?php

declare(strict_types=1);

namespace QRCodeWizard\Infrastructure;

use Endroid\QrCode\Writer\PngWriter;
use QRCodeWizard\Domain\{QrImage, QrRenderer};

final class EndroidPngRenderer implements QrRenderer
{
    public function render(string $payload): QrImage
    {
        return EndroidImageBuilder::build($payload, new PngWriter(), 'png');
    }
}
