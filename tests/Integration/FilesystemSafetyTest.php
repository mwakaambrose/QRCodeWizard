<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\Domain\QrImage;
use QRCodeWizard\Infrastructure\FilesystemImageStore;

final class FilesystemSafetyTest extends TestCase
{
    public function testAtomicReplacementDoesNotFollowAnExistingFileSymlink(): void
    {
        $directory = sys_get_temp_dir() . '/qr-atomic-' . bin2hex(random_bytes(6));
        mkdir($directory);
        file_put_contents($directory . '/original', 'keep');
        symlink($directory . '/original', $directory . '/qr_00001.svg');
        try {
            $store = new FilesystemImageStore($directory);
            $store->save(new QrImage('qr_00001.svg', 'image/svg+xml', 'new'));
            self::assertSame('keep', file_get_contents($directory . '/original'));
            self::assertFalse(is_link($directory . '/qr_00001.svg'));
            self::assertSame('new', $store->read('qr_00001.svg'));
        } finally {
            unlink($directory . '/qr_00001.svg'); unlink($directory . '/original'); rmdir($directory);
        }
    }

    public function testFailedRenamePreservesDestinationAndCleansTemporaryFile(): void
    {
        $directory = sys_get_temp_dir() . '/qr-failure-' . bin2hex(random_bytes(6));
        mkdir($directory); mkdir($directory . '/qr_00001.svg');
        file_put_contents($directory . '/qr_00001.svg/keep', 'existing');
        try {
            $store = new FilesystemImageStore($directory);
            try { $store->save(new QrImage('qr_00001.svg', 'image/svg+xml', 'new')); self::fail('Expected failed rename'); }
            catch (RuntimeException $exception) { self::assertStringContainsString('replace', $exception->getMessage()); }
            self::assertSame('existing', file_get_contents($directory . '/qr_00001.svg/keep'));
            self::assertSame([], glob($directory . '/.qr-*'));
        } finally {
            foreach (glob($directory . '/.qr-*') ?: [] as $file) { unlink($file); }
            unlink($directory . '/qr_00001.svg/keep'); rmdir($directory . '/qr_00001.svg'); rmdir($directory);
        }
    }

    public function testDirectoryConflictAndUnsafeReadFailExplicitly(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'qr-conflict-');
        try {
            $store = new FilesystemImageStore($path);
            try { $store->save(new QrImage('qr_00001.svg', 'image/svg+xml', 'bytes')); self::fail('Expected directory failure'); }
            catch (RuntimeException) { self::assertTrue(true); }
            $this->expectException(InvalidArgumentException::class);
            $store->read('../outside');
        } finally { unlink($path); }
    }
}
