<?php

declare(strict_types=1);

namespace QRCodeWizard\Adapters;

use InvalidArgumentException;
use RuntimeException;
use QRCodeWizard\Contracts\QrCodeImageSaverInterface;

/**
 * SRP: saves images in a folder. Implements the saver contract used by the generator
 * (DIP/OCP), reporting write failures as exceptions (LSP).
 */
final readonly class FolderQrCodeImageSaver implements QrCodeImageSaverInterface
{
    /** Keep a valid output folder as private readonly configuration. */
    public function __construct(private string $outputFolder)
    {
        if (trim($outputFolder) === '' || str_contains($outputFolder, "\0")) {
            throw new InvalidArgumentException('Output directory must be a nonempty filesystem path.');
        }
    }

    /**
     * SRP: save image bytes to a file. LSP: throw if the complete write fails.
     * Direct writes overwrite existing files; they are not atomic.
     *
     * @throws InvalidArgumentException If the filename is unsafe or bytes are empty.
     * @throws RuntimeException If folder creation or writing fails, or the file is a symlink.
     */
    public function saveImageFile(string $fileName, string $imageBytes): void
    {
        if (!preg_match('/\Aqr_[0-9]+\.[a-z0-9]+\z/', $fileName) || $imageBytes === '') {
            throw new InvalidArgumentException('An image needs a safe QR filename and nonempty content.');
        }

        if (!is_dir($this->outputFolder) && !@mkdir($this->outputFolder, 0755, true)) {
            throw new RuntimeException('Cannot create output directory: ' . $this->outputFolder);
        }

        $outputFilePath = $this->outputFolder . '/' . $fileName;
        if (is_link($outputFilePath)) {
            throw new RuntimeException('Cannot overwrite a symbolic link: ' . $fileName);
        }
        if (@file_put_contents($outputFilePath, $imageBytes) !== strlen($imageBytes)) {
            throw new RuntimeException('Cannot write complete image: ' . $fileName);
        }
    }
}
