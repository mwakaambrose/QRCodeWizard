<?php

declare(strict_types=1);

namespace QRCodeWizard\Infrastructure;

use InvalidArgumentException;
use QRCodeWizard\Domain\{ImageStore, QrImage};
use RuntimeException;

final readonly class FilesystemImageStore implements ImageStore
{
    public function __construct(private string $directory)
    {
        if (trim($directory) === '' || str_contains($directory, "\0")) {
            throw new InvalidArgumentException('Output directory must be a nonempty filesystem path.');
        }
    }

    public function save(QrImage $image): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Cannot create output directory: ' . $this->directory);
        }
        if (@file_put_contents($this->directory . '/' . $image->filename(), $image->bytes()) !== strlen($image->bytes())) {
            throw new RuntimeException('Cannot write image: ' . $image->filename());
        }
    }

    public function read(string $filename): string
    {
        if (!preg_match('/\Aqr_[0-9]+\.(png|svg)\z/', $filename)) {
            throw new InvalidArgumentException('Unsafe image filename.');
        }
        $bytes = @file_get_contents($this->directory . '/' . $filename);
        if ($bytes === false) { throw new RuntimeException('Image not found: ' . $filename); }
        return $bytes;
    }
}
