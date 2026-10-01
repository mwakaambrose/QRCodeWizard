<?php

declare(strict_types=1);

namespace QRCodeWizard\Domain;

use InvalidArgumentException;

final readonly class QrImage
{
    public function __construct(private string $filename, private string $mimeType, private string $bytes)
    {
        if (!preg_match('/\Aqr_[0-9]+\.(png|svg)\z/', $filename)
            || !in_array($mimeType, ['image/png', 'image/svg+xml'], true)
            || ($mimeType === 'image/png' && !str_ends_with($filename, '.png'))
            || ($mimeType === 'image/svg+xml' && !str_ends_with($filename, '.svg'))
            || $bytes === '') {
            throw new InvalidArgumentException('An image needs a safe QR filename, matching MIME type, and nonempty content.');
        }
    }

    public function filename(): string { return $this->filename; }
    public function mimeType(): string { return $this->mimeType; }
    public function bytes(): string { return $this->bytes; }
}
