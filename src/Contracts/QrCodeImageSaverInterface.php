<?php

declare(strict_types=1);

namespace QRCodeWizard\Contracts;

/**
 * Describes the persistence capability required by QrCodeBatchGenerator.
 *
 * ISP (Interface Segregation): exposes only save; clients need no read/list/delete API.
 * DIP (Dependency Inversion): the generator depends on this contract, not a filesystem.
 * OCP (Open/Closed): other stores can be injected without changing generation.
 * LSP (Liskov Substitution): implementations must preserve the save/failure contract.
 */
interface QrCodeImageSaverInterface
{
    /**
     * Save nonempty image bytes under a safe QR filename, replacing an existing image.
     *
     * SRP/ISP: persistence requires no rendering or numbering knowledge.
     * LSP: return only after complete storage succeeds; throw on invalid input/failure.
     * Safe names follow qr_<decimal payload>.<lowercase alphanumeric extension>.
     * Polymorphism: the generator calls the injected store through this method.
     *
     * @param string $fileName Safe QR filename, such as qr_00001.png.
     * @param string $imageBytes Encoded image contents to save.
     * @throws \InvalidArgumentException If the filename is unsafe or bytes are empty.
     * @throws \Throwable If persistence fails.
     */
    public function saveImageFile(string $fileName, string $imageBytes): void;
}
