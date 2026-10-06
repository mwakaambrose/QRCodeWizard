<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\Adapters\FolderQrCodeImageSaver;

final class FolderQrCodeImageSaverTest extends TestCase
{
    public function testExistingFileSymlinkIsRejectedWithoutChangingItsTarget(): void
    {
        $outputFolder = sys_get_temp_dir() . '/qr-symlink-' . bin2hex(random_bytes(6));
        mkdir($outputFolder);
        file_put_contents($outputFolder . '/original', 'keep');
        symlink($outputFolder . '/original', $outputFolder . '/qr_00001.svg');
        try {
            $imageSaver = new FolderQrCodeImageSaver($outputFolder);
            try {
                $imageSaver->saveImageFile('qr_00001.svg', 'new');
                self::fail('Expected symlink rejection');
            } catch (RuntimeException) {
                self::assertSame('keep', file_get_contents($outputFolder . '/original'));
                self::assertTrue(is_link($outputFolder . '/qr_00001.svg'));
            }
        } finally {
            unlink($outputFolder . '/qr_00001.svg'); unlink($outputFolder . '/original'); rmdir($outputFolder);
        }
    }

    public function testWritingOverADirectoryFailsWithoutChangingItsContents(): void
    {
        $outputFolder = sys_get_temp_dir() . '/qr-failure-' . bin2hex(random_bytes(6));
        mkdir($outputFolder); mkdir($outputFolder . '/qr_00001.svg');
        file_put_contents($outputFolder . '/qr_00001.svg/keep', 'existing');
        try {
            $imageSaver = new FolderQrCodeImageSaver($outputFolder);
            try { $imageSaver->saveImageFile('qr_00001.svg', 'new'); self::fail('Expected write failure'); }
            catch (RuntimeException) { self::assertTrue(true); }
            self::assertSame('existing', file_get_contents($outputFolder . '/qr_00001.svg/keep'));
        } finally {
            unlink($outputFolder . '/qr_00001.svg/keep'); rmdir($outputFolder . '/qr_00001.svg'); rmdir($outputFolder);
        }
    }

    public function testSavingAndReplacingAFileUsesNormalFilesystemPermissions(): void
    {
        $outputFolder = sys_get_temp_dir() . '/qr-permissions-' . bin2hex(random_bytes(6));
        $previousUmask = umask(0022);
        try {
            $imageSaver = new FolderQrCodeImageSaver($outputFolder);
            $fileName = 'qr_00001.svg';
            $imageSaver->saveImageFile($fileName, 'first');
            $path = $outputFolder . '/qr_00001.svg';
            clearstatcache(true, $path);
            self::assertSame(0644, fileperms($path) & 0777);
            chmod($path, 0640);
            $imageSaver->saveImageFile('qr_00001.svg', 'second');
            clearstatcache(true, $path);
            self::assertSame(0640, fileperms($path) & 0777);
            self::assertSame('second', file_get_contents($outputFolder . '/qr_00001.svg'));
        } finally {
            umask($previousUmask);
            if (is_file($outputFolder . '/qr_00001.svg')) { unlink($outputFolder . '/qr_00001.svg'); }
            if (is_dir($outputFolder)) { rmdir($outputFolder); }
        }
    }

    public function testDirectoryConflictAndUnsafeFilenameFailExplicitly(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'qr-conflict-');
        try {
            $imageSaver = new FolderQrCodeImageSaver($path);
            try { $imageSaver->saveImageFile('qr_00001.svg', 'bytes'); self::fail('Expected directory failure'); }
            catch (RuntimeException) { self::assertTrue(true); }
            $this->expectException(InvalidArgumentException::class);
            $imageSaver->saveImageFile('../outside', 'bytes');
        } finally { unlink($path); }
    }
    public function testEmptyBytesAndUnsafeNamesAreRejectedBeforeWriting(): void
    {
        $outputFolder = sys_get_temp_dir() . '/qr-invalid-' . bin2hex(random_bytes(6));
        $imageSaver = new FolderQrCodeImageSaver($outputFolder);
        foreach (['../image.png', 'dir/image.png', 'dir\\image.png', ''] as $fileName) {
            try { $imageSaver->saveImageFile($fileName, 'bytes'); self::fail('Unsafe name accepted'); }
            catch (InvalidArgumentException) { self::assertDirectoryDoesNotExist($outputFolder); }
        }
        $this->expectException(InvalidArgumentException::class);
        $imageSaver->saveImageFile('qr_00001.png', '');
    }

    public function testInvalidOutputPathsAreRejected(): void
    {
        foreach (['', '  ', "invalid\0path"] as $outputFolder) {
            try { new FolderQrCodeImageSaver($outputFolder); self::fail('Invalid directory accepted'); }
            catch (InvalidArgumentException) { self::assertTrue(true); }
        }
    }
}
