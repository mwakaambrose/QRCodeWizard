<?php

declare(strict_types=1);

namespace QRCodeWizard;

use QRCodeWizard\Contracts\{QrCodeImageRendererInterface, QrCodeImageSaverInterface};

use InvalidArgumentException;

/**
 * Coordinates numbered QR batches without knowing how images are rendered or saved.
 * 
 * 1. final — Prevents inheritance. Prevents subclasses from changing intended behavior. 
 * - Encourages composition instead of inheritance.
 * 2. readonly — Prevents property reassignment. Makes the object immutable after construction. Can't do this;
 * -$generator->batchSize = 200; // Error: Cannot modify readonly property
 *
 * SRP (Single Responsibility): owns the numbered batch workflow.
 * DIP (Dependency Inversion): depends on QrCodeImageRendererInterface and QrCodeImageSaverInterface abstractions.
 * OCP (Open/Closed): new renderer/store implementations require no generator edits.
 * Polymorphism: renderQrCodeImage(), getImageFileExtension() and saveImageFile()
 * dispatch to the injected objects.
 */
final readonly class QrCodeBatchGenerator
{
    /**
     * DIP: inject collaborators through interfaces instead of constructing adapters.
     * Composition and encapsulation: retain those collaborators as private readonly state.
     */
    public function __construct(
        private QrCodeImageRendererInterface $imageRenderer,
        private QrCodeImageSaverInterface $imageSaver,
    ) {}

    /**
     * Validate a range, then render and save one numbered image at a time.
     *
     * SRP: coordinates the batch; rendering and persistence remain delegated.
     * Structured programming: if selects invalid ranges, for repeats the batch,
     * and each iteration formats a number, renders an image, then saves it in order.
     * OCP/DIP: uses only interface methods, so adapters can be substituted.
     * LSP (Liskov Substitution): relies on the interfaces' output/failure contracts;
     * implementations must honor them, not merely declare that they implement them.
     *
     * @param int $startNumber First positive number to encode.
     * @param int $numberOfCodes Number of images to generate; the range must fit a PHP integer.
     * @return int Completed image count, returned only if every save succeeds.
     * @throws InvalidArgumentException If the range is invalid or overflows.
     * @throws \Throwable Rendering/storage failures propagate; earlier files remain.
     */
    public function generateBatch(int $startNumber = 1, int $numberOfCodes = 10000): int
    {
        if ($startNumber < 1 || $numberOfCodes < 1 || $numberOfCodes - 1 > PHP_INT_MAX - $startNumber) {
            throw new InvalidArgumentException('Start/count must be positive and the range must fit a PHP integer.');
        }

        $imageFileExtension = $this->imageRenderer->getImageFileExtension();
        for ($numberOffset = 0; $numberOffset < $numberOfCodes; ++$numberOffset) {
            $numberText = str_pad((string) ($startNumber + $numberOffset), 5, '0', STR_PAD_LEFT);
            $fileName = 'qr_' . $numberText . '.' . $imageFileExtension;
            $imageBytes = $this->imageRenderer->renderQrCodeImage($numberText);
            $this->imageSaver->saveImageFile($fileName, $imageBytes);
        }

        return $numberOfCodes;
    }
}
