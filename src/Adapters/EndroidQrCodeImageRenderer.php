<?php

declare(strict_types=1);

namespace QRCodeWizard\Adapters;

use QRCodeWizard\Contracts\QrCodeImageRendererInterface;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\WriterInterface;
use InvalidArgumentException;

/**
 * Adapts Endroid's writer API to the application's rendering contract.
 *
 * SRP (Single Responsibility): owns QR rendering settings and image creation.
 * DIP (Dependency Inversion): receives Endroid's WriterInterface abstraction.
 * OCP (Open/Closed): a compatible writer can be supplied without changing this class.
 * LSP (Liskov Substitution): implements QrCodeImageRendererInterface's decimal-payload/byte contract.
 * Polymorphism: PngWriter and SvgWriter implement WriterInterface; the same Builder
 * calls their different write() implementations. SVG does not include a text label.
 */
final readonly class EndroidQrCodeImageRenderer implements QrCodeImageRendererInterface
{
    /**
     * DIP/OCP: inject a writer and its matching filename extension.
     *
     * For example, use PngWriter with 'png' or SvgWriter with 'svg'. The caller owns
     * this pairing; syntax validation here does not verify the writer's format.
     * Private readonly fields encapsulate the chosen configuration.
     *
     * @throws InvalidArgumentException If the extension is not lowercase alphanumeric.
     */
    public function __construct(
        private WriterInterface $imageWriter,
        private string $imageFileExtension = 'png',
    ) {
        if (!preg_match('/\A[a-z0-9]+\z/', $imageFileExtension)) {
            throw new InvalidArgumentException('Image extension must be lowercase alphanumeric.');
        }
    }

    /**
     * ISP/LSP: provide the stable naming detail promised by QrCodeImageRendererInterface, without
     * exposing the concrete writer to QrCodeBatchGenerator.
     */
    public function getImageFileExtension(): string
    {
        return $this->imageFileExtension;
    }

    /**
     * Create image bytes using the original QR settings and injected writer.
     *
     * SRP: renders only; does not number batches, name files or persist images.
     * LSP: accepts decimal payloads and returns the renderer contract's image bytes.
     * Polymorphism: Builder delegates writing to the injected WriterInterface object.
     *
     * @param string $numberText Decimal number to encode, including any leading zeros.
     * @return string Encoded image bytes, ready to save to a file.
     * @throws InvalidArgumentException If the payload is not a nonempty decimal string.
     * @throws \Throwable If Endroid cannot render the image.
     */
    public function renderQrCodeImage(string $numberText): string
    {
        if (!preg_match('/\A[0-9]+\z/', $numberText)) {
            throw new InvalidArgumentException('QR payload must be a nonempty decimal string.');
        }
        $renderedImage = (new Builder(
            writer: $this->imageWriter,
            writerOptions: [],
            validateResult: false,
            data: $numberText,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            labelText: $numberText,
            labelFont: new OpenSans(16),
            labelAlignment: LabelAlignment::Center,
        ))->build();
        return $renderedImage->getString();
    }
}
