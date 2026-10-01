<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\Domain\QrImage;
use QRCodeWizard\Infrastructure\{FilesystemImageStore, InMemoryImageStore, EndroidPngRenderer, EndroidSvgRenderer};

final class AdapterContractTest extends TestCase
{
    public function testStoresReadBackAndReplaceTheSameFilename(): void
    {
        $directory = sys_get_temp_dir() . '/qr-store-' . bin2hex(random_bytes(6));
        try {
            foreach ([new FilesystemImageStore($directory), new InMemoryImageStore()] as $store) {
                foreach (['first', 'replacement'] as $bytes) {
                    $store->save(new QrImage('qr_00001.svg', 'image/svg+xml', $bytes));
                    self::assertSame($bytes, $store->read('qr_00001.svg'));
                }
                try { $store->read('qr_99999.svg'); self::fail('Missing read accepted'); }
                catch (RuntimeException) { self::assertTrue(true); }
            }
        } finally {
            if (is_file($directory . '/qr_00001.svg')) { unlink($directory . '/qr_00001.svg'); }
            if (is_dir($directory)) { rmdir($directory); }
        }
    }

    public function testRenderersRejectInvalidPayloadsConsistently(): void
    {
        foreach ([new EndroidPngRenderer(), new EndroidSvgRenderer()] as $renderer) {
            foreach (['', '../escape', 'hello'] as $payload) {
                try { $renderer->render($payload); self::fail('Invalid payload accepted'); }
                catch (InvalidArgumentException) { self::assertTrue(true); }
            }
        }
    }
}
