<?php

declare(strict_types=1);

namespace QRCodeWizard\Infrastructure;

use QRCodeWizard\Domain\{ImageStore, QrImage};
use RuntimeException;

final class InMemoryImageStore implements ImageStore
{
    /** @var array<string, string> */
    private array $images = [];

    public function save(QrImage $image): void { $this->images[$image->filename()] = $image->bytes(); }

    public function read(string $filename): string
    {
        return $this->images[$filename] ?? throw new RuntimeException('Image not found: ' . $filename);
    }
}
