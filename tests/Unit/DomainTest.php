<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use QRCodeWizard\Domain\BatchOptions;
use QRCodeWizard\Domain\QrImage;

final class DomainTest extends TestCase
{
    public function testRejectsInvalidRanges(): void
    {
        foreach ([[0, 1], [1, 0], [-1, 2], [PHP_INT_MAX, 2]] as [$start, $count]) {
            try { new BatchOptions($start, $count); self::fail('Invalid range accepted'); }
            catch (InvalidArgumentException) { self::assertTrue(true); }
        }
    }

    public function testDefaultsAndMaximumSingleValueAreValid(): void
    {
        self::assertSame(10000, (new BatchOptions())->count());
        self::assertSame(PHP_INT_MAX, (new BatchOptions(PHP_INT_MAX, 1))->start());
    }

    public function testImageRejectsTraversalAndEmptyContent(): void
    {
        foreach (['../image.png', 'dir/image.png', 'dir\\image.png', ''] as $filename) {
            try { new QrImage($filename, 'image/png', 'bytes'); self::fail('Unsafe name accepted'); }
            catch (InvalidArgumentException) { self::assertTrue(true); }
        }
        $this->expectException(InvalidArgumentException::class);
        new QrImage('qr_00001.png', 'image/png', '');
    }
}
