<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\Application\GenerateBatch;
use QRCodeWizard\Domain\{BatchOptions, ImageStore, PayloadSource, QrImage, QrRenderer};

final class GenerateBatchTest extends TestCase
{
    public function testStorageFailurePropagatesInsteadOfReportingSuccess(): void
    {
        $source = new class implements PayloadSource {
            public function payloads(BatchOptions $options): iterable { yield '00001'; }
        };
        $renderer = new class implements QrRenderer {
            public function render(string $payload): QrImage { return new QrImage('qr_' . $payload . '.svg', 'image/svg+xml', '<svg/>'); }
        };
        $store = new class implements ImageStore {
            public function save(QrImage $image): void { throw new RuntimeException('Storage unavailable'); }
            public function read(string $filename): string { throw new RuntimeException('Missing'); }
        };
        $this->expectExceptionMessage('Storage unavailable');
        (new GenerateBatch($source, $renderer, $store))->generate(new BatchOptions(1, 1));
    }
}
