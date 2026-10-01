<?php

declare(strict_types=1);

namespace QRCodeWizard\Infrastructure;

use Endroid\QrCode\Writer\SvgWriter;
use QRCodeWizard\Domain\{QrImage, QrRenderer};

final class EndroidSvgRenderer implements QrRenderer
{
    public function render(string $payload): QrImage
    {
        return EndroidImageBuilder::build($payload, new SvgWriter(), 'svg');
    }
}
