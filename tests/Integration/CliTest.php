<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CliTest extends TestCase
{
    private function command(string $arguments): array
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 2) . '/bin/generate') . ' ' . $arguments . ' 2>&1', $lines, $exit);
        return [$exit, implode("\n", $lines)];
    }

    public function testHelpAndInvalidArguments(): void
    {
        [$exit, $text] = $this->command('--help');
        self::assertSame(0, $exit); self::assertStringContainsString('--count', $text);
        foreach (['--unknown', '--count', '--count=0', '--count=-1', '--count=abc', '--start=999999999999999999999', '--format=pdf', '--output='] as $args) {
            [$exit, $text] = $this->command($args);
            self::assertSame(1, $exit, $text); self::assertStringContainsString('Error:', $text);
        }
    }

    public function testRealCliCreatesOnlyRequestedRangeInPathWithSpaces(): void
    {
        $directory = sys_get_temp_dir() . '/qr cli ' . bin2hex(random_bytes(6));
        foreach (['png', 'svg'] as $format) {
            [$exit, $text] = $this->command('--start=7 --count=2 --format=' . $format . ' --output=' . escapeshellarg($directory));
            try {
                self::assertSame(0, $exit, $text);
                self::assertStringContainsString('Generated 2 QR codes', $text);
                self::assertFileExists($directory . '/qr_00007.' . $format);
                self::assertFileExists($directory . '/qr_00008.' . $format);
                self::assertCount(2, glob($directory . '/*'));
            } finally {
                foreach (glob($directory . '/*') ?: [] as $file) { unlink($file); }
                if (is_dir($directory)) { rmdir($directory); }
            }
        }
    }
}
