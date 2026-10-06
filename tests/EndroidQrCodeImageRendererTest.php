<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Endroid\QrCode\Writer\{PngWriter, SvgWriter};
use QRCodeWizard\Adapters\{EndroidQrCodeImageRenderer, FolderQrCodeImageSaver};
use QRCodeWizard\QrCodeBatchGenerator;

final class EndroidQrCodeImageRendererTest extends TestCase
{
    public function testBothWritersWorkWithTheSameGenerator(): void
    {
        foreach ([new PngWriter(), new SvgWriter()] as $writer) {
            $outputFolder = sys_get_temp_dir() . '/qr-generation-' . bin2hex(random_bytes(6));
            $imageRenderer = new EndroidQrCodeImageRenderer($writer, $writer instanceof PngWriter ? 'png' : 'svg');
            try {
                self::assertSame(2, (new QrCodeBatchGenerator($imageRenderer, new FolderQrCodeImageSaver($outputFolder)))->generateBatch(9999, 2));
                $imageFileExtension = $writer instanceof PngWriter ? 'png' : 'svg';
                self::assertSame($imageFileExtension, $imageRenderer->getImageFileExtension());
                foreach (['09999', '10000'] as $numberText) {
                    $imageBytes = file_get_contents($outputFolder . '/qr_' . $numberText . '.' . $imageFileExtension);
                    if ($imageFileExtension === 'png') {
                        self::assertSame('image/png', getimagesizefromstring($imageBytes)['mime']);
                        self::assertGreaterThan(300, getimagesizefromstring($imageBytes)[1]);
                    } else {
                        $xml = simplexml_load_string($imageBytes);
                        self::assertNotFalse($xml);
                        self::assertSame('svg', $xml->getName());
                    }
                }
            } finally {
                foreach (glob($outputFolder . '/*') ?: [] as $file) { unlink($file); }
                if (is_dir($outputFolder)) { rmdir($outputFolder); }
            }
        }
    }

    public function testBothWritersRejectInvalidPayloads(): void
    {
        foreach ([new PngWriter(), new SvgWriter()] as $writer) {
            $imageRenderer = new EndroidQrCodeImageRenderer($writer, $writer instanceof PngWriter ? 'png' : 'svg');
            foreach (['', '../escape', 'hello'] as $numberText) {
                try { $imageRenderer->renderQrCodeImage($numberText); self::fail('Invalid payload accepted'); }
                catch (InvalidArgumentException) { self::assertTrue(true); }
            }
        }
    }

    public function testUnsafeExtensionsAreRejected(): void
    {
        foreach (['', '../png', 'PNG', 'png/other'] as $imageFileExtension) {
            try { new EndroidQrCodeImageRenderer(new PngWriter(), $imageFileExtension); self::fail('Unsafe extension accepted'); }
            catch (InvalidArgumentException) { self::assertTrue(true); }
        }
    }

    public function testPngMatchesOriginalScriptBytes(): void
    {
        $outputFolder = sys_get_temp_dir() . '/qr-comparison-' . bin2hex(random_bytes(6));
        mkdir($outputFolder);
        $source = file_get_contents(__DIR__ . '/Fixtures/legacy_generate_qrcodes.php');
        $source = str_replace("require 'vendor/autoload.php';", 'require ' . var_export(dirname(__DIR__) . '/vendor/autoload.php', true) . ';', $source);
        $source = str_replace('$i <= 10000', '$i <= 1', $source);
        file_put_contents($outputFolder . '/generate.php', $source);
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($outputFolder . '/generate.php'), $output, $exit);
        try {
            self::assertSame(0, $exit);
            self::assertSame(file_get_contents($outputFolder . '/qrcodes/qr_00001.png'), (new EndroidQrCodeImageRenderer(new PngWriter()))->renderQrCodeImage('00001'));
        } finally {
            unlink($outputFolder . '/qrcodes/qr_00001.png'); rmdir($outputFolder . '/qrcodes');
            unlink($outputFolder . '/generate.php'); rmdir($outputFolder);
        }
    }
}
