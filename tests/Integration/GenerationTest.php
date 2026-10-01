<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\Application\GenerateBatch;
use QRCodeWizard\Domain\BatchOptions;
use QRCodeWizard\Infrastructure\{SequentialPayloadSource, EndroidPngRenderer, EndroidSvgRenderer, InMemoryImageStore};

final class GenerationTest extends TestCase
{
    public function testBothRenderersWorkWithTheSameApplication(): void
    {
        foreach ([new EndroidPngRenderer(), new EndroidSvgRenderer()] as $renderer) {
            $store = new InMemoryImageStore();
            $result = (new GenerateBatch(new SequentialPayloadSource(), $renderer, $store))->generate(new BatchOptions(9999, 2));
            self::assertSame(2, $result->generatedCount());
            $extension = $renderer instanceof EndroidPngRenderer ? 'png' : 'svg';
            foreach (['09999', '10000'] as $payload) {
                $bytes = $store->read('qr_' . $payload . '.' . $extension);
                if ($extension === 'png') { self::assertSame('image/png', getimagesizefromstring($bytes)['mime']); }
                else { $xml = simplexml_load_string($bytes); self::assertNotFalse($xml); self::assertSame('svg', $xml->getName()); }
            }
        }
    }

    public function testSequentialSourceSupportsDefaultsAndIntegerBoundary(): void
    {
        $source = new SequentialPayloadSource();
        $values = iterator_to_array($source->payloads(new BatchOptions()));
        self::assertCount(10000, $values);
        self::assertSame('00001', $values[0]);
        self::assertSame('10000', $values[9999]);
        self::assertSame([(string) PHP_INT_MAX], iterator_to_array($source->payloads(new BatchOptions(PHP_INT_MAX, 1))));
    }

    public function testPngMatchesOriginalScriptBytes(): void
    {
        $directory = sys_get_temp_dir() . '/qr-comparison-' . bin2hex(random_bytes(6));
        mkdir($directory);
        $source = file_get_contents(__DIR__ . '/../Fixtures/legacy_generate_qrcodes.php');
        $source = str_replace("require 'vendor/autoload.php';", 'require ' . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ';', $source);
        $source = str_replace('$i <= 10000', '$i <= 1', $source);
        file_put_contents($directory . '/generate.php', $source);
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($directory . '/generate.php'), $output, $exit);
        try {
            self::assertSame(0, $exit);
            self::assertSame(file_get_contents($directory . '/qrcodes/qr_00001.png'), (new EndroidPngRenderer())->render('00001')->bytes());
        } finally {
            unlink($directory . '/qrcodes/qr_00001.png'); rmdir($directory . '/qrcodes');
            unlink($directory . '/generate.php'); rmdir($directory);
        }
    }
}
