<?php

declare(strict_types=1);

namespace QRCodeWizard\Contracts;

/**
 * Describes the rendering capability required by QrCodeBatchGenerator.
 *
 * ISP (Interface Segregation): exposes rendering and its file extension, no storage.
 * DIP (Dependency Inversion): lets the generator depend on an abstraction.
 * OCP (Open/Closed): additional renderers can implement this contract independently.
 * LSP (Liskov Substitution): every implementation must honor the contracts below.
 */
interface QrCodeImageRendererInterface
{
    /**
     * Render a nonempty decimal payload to nonempty image bytes, or throw.
     *
     * SRP/ISP: rendering has no filesystem side effects required by this contract.
     * LSP: accept valid decimal payloads and return bytes matching getImageFileExtension();
     * reject invalid payloads consistently rather than silently reporting success.
     * Polymorphism: the generator invokes whichever renderer was injected.
     *
     * @param string $numberText Decimal number to encode, including any leading zeros.
     * @return string Encoded image bytes, ready to save to a file.
     * @throws \InvalidArgumentException If the payload is not a nonempty decimal string.
     * @throws \Throwable If rendering fails.
     */
    public function renderQrCodeImage(string $numberText): string;

    /**
     * Return a stable lowercase alphanumeric extension matching the rendered format.
     *
     * ISP/OCP: supplies the naming detail without exposing writer-specific internals
     * or requiring the generator to branch on concrete renderer types.
     * LSP: the extension must be safe for a filename and agree with renderQrCodeImage() output.
     */
    public function getImageFileExtension(): string;
}
