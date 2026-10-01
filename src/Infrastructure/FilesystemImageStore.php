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
        $destination = $this->directory . '/' . $image->filename();
        clearstatcache(true, $destination);
        $permissions = 0666 & ~umask();
        if (is_file($destination) && !is_link($destination)) {
            $existingPermissions = @fileperms($destination);
            if ($existingPermissions === false) { throw new RuntimeException('Cannot inspect image permissions.'); }
            $permissions = $existingPermissions & 0777;
        }
        $temporary = @tempnam($this->directory, '.qr-');
        if ($temporary === false) { throw new RuntimeException('Cannot create temporary image file.'); }
        try {
            if (realpath(dirname($temporary)) !== realpath($this->directory)) {
                throw new RuntimeException('Temporary file must be in the output directory.');
            }
            if (@file_put_contents($temporary, $image->bytes()) !== strlen($image->bytes())) {
                throw new RuntimeException('Cannot write complete image: ' . $image->filename());
            }
            if (!@chmod($temporary, $permissions)) {
                throw new RuntimeException('Cannot set image permissions.');
            }
            if (!@rename($temporary, $destination)) {
                throw new RuntimeException('Cannot replace image: ' . $image->filename());
            }
        } finally {
            if (is_file($temporary)) { @unlink($temporary); }
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
