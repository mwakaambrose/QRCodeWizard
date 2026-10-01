<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LegacyGeneratorTest extends TestCase
{
    public function testOriginalScriptCreatesLabeledPngsAtRangeBoundaries(): void
    {
        foreach ([1 => '00001', 10000 => '10000'] as $start => $payload) {
            $directory = sys_get_temp_dir() . '/qr-legacy-' . bin2hex(random_bytes(6));
            mkdir($directory);
            $source = file_get_contents(__DIR__ . '/../Fixtures/legacy_generate_qrcodes.php');
            $source = str_replace("require 'vendor/autoload.php';", 'require ' . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ';', $source);
            $source = str_replace('$i = 1; $i <= 10000', '$i = ' . $start . '; $i <= ' . $start, $source);
            file_put_contents($directory . '/generate.php', $source);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($directory . '/generate.php') . ' 2>&1', $lines, $exit);
            try {
                self::assertSame(0, $exit, implode("\n", $lines));
                $path = $directory . '/qrcodes/qr_' . $payload . '.png';
                self::assertFileExists($path);
                self::assertSame('image/png', getimagesize($path)['mime']);
                self::assertGreaterThan(300, getimagesize($path)[1], 'Label adds height to the QR image');
                self::assertCount(1, glob($directory . '/qrcodes/*.png'));
            } finally {
                foreach (glob($directory . '/qrcodes/*') ?: [] as $file) { unlink($file); }
                if (is_dir($directory . '/qrcodes')) { rmdir($directory . '/qrcodes'); }
                unlink($directory . '/generate.php');
                rmdir($directory);
            }
        }
    }
}
