<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\QrCodeBatchGenerator;
use QRCodeWizard\Contracts\{QrCodeImageRendererInterface, QrCodeImageSaverInterface};

final class QrCodeBatchGeneratorTest extends TestCase
{
    private function createRecordingImageSaver(): QrCodeImageSaverInterface
    {
        return new class implements QrCodeImageSaverInterface {
            /** @var array<string, string> */
            public array $images = [];
            public function saveImageFile(string $fileName, string $imageBytes): void { $this->images[$fileName] = $imageBytes; }
        };
    }

    private function createNumberTextRenderer(): QrCodeImageRendererInterface
    {
        return new class implements QrCodeImageRendererInterface {
            public function renderQrCodeImage(string $numberText): string { return $numberText; }
            public function getImageFileExtension(): string { return 'svg'; }
        };
    }

    public function testDefaultRangeAndIntegerBoundary(): void
    {
        $imageSaver = $this->createRecordingImageSaver();
        $batchGenerator = new QrCodeBatchGenerator($this->createNumberTextRenderer(), $imageSaver);
        self::assertSame(10000, $batchGenerator->generateBatch());
        self::assertCount(10000, $imageSaver->images);
        self::assertSame('00001', $imageSaver->images['qr_00001.svg']);
        self::assertSame('10000', $imageSaver->images['qr_10000.svg']);
        self::assertSame(1, $batchGenerator->generateBatch(startNumber: PHP_INT_MAX, numberOfCodes: 1));
        self::assertSame((string) PHP_INT_MAX, $imageSaver->images['qr_' . PHP_INT_MAX . '.svg']);
    }

    public function testInvalidRangesFailBeforeStorage(): void
    {
        $imageSaver = $this->createRecordingImageSaver();
        $batchGenerator = new QrCodeBatchGenerator($this->createNumberTextRenderer(), $imageSaver);
        foreach ([[0, 1], [1, 0], [-1, 2], [PHP_INT_MAX, 2]] as [$start, $count]) {
            try { $batchGenerator->generateBatch($start, $count); self::fail('Invalid range accepted'); }
            catch (InvalidArgumentException) { self::assertSame([], $imageSaver->images); }
        }
    }

    public function testStorageFailurePropagates(): void
    {
        $imageSaver = new class implements QrCodeImageSaverInterface {
            public function saveImageFile(string $fileName, string $imageBytes): void { throw new RuntimeException('Storage unavailable'); }
        };
        $this->expectExceptionMessage('Storage unavailable');
        (new QrCodeBatchGenerator($this->createNumberTextRenderer(), $imageSaver))->generateBatch(numberOfCodes: 1);
    }

    public function testRendererFailurePropagatesBeforeStorage(): void
    {
        $imageSaver = $this->createRecordingImageSaver();
        $imageRenderer = new class implements QrCodeImageRendererInterface {
            public function renderQrCodeImage(string $numberText): string { throw new RuntimeException('Rendering unavailable'); }
            public function getImageFileExtension(): string { return 'png'; }
        };
        try { (new QrCodeBatchGenerator($imageRenderer, $imageSaver))->generateBatch(numberOfCodes: 1); self::fail('Expected rendering failure'); }
        catch (RuntimeException $exception) {
            self::assertSame('Rendering unavailable', $exception->getMessage());
            self::assertSame([], $imageSaver->images);
        }
    }
}
