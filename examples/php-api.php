<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Endroid\QrCode\Writer\SvgWriter;
use QRCodeWizard\QrCodeBatchGenerator;
use QRCodeWizard\Adapters\{EndroidQrCodeImageRenderer, FolderQrCodeImageSaver};

$outputFolder = sys_get_temp_dir() . '/qr-example-' . bin2hex(random_bytes(6));
$batchGenerator = new QrCodeBatchGenerator(
    new EndroidQrCodeImageRenderer(new SvgWriter(), 'svg'),
    new FolderQrCodeImageSaver($outputFolder),
);

try {
    $generatedCount = $batchGenerator->generateBatch(startNumber: 3, numberOfCodes: 5);
    echo 'Generated ' . $generatedCount . " SVGs.\n";
    $imageBytes = file_get_contents($outputFolder . '/qr_00007.svg');
    if ($imageBytes === false) { throw new RuntimeException('Cannot read generated example image.'); }
    echo 'Last image: ' . strlen($imageBytes) . " bytes.\n";
} finally {
    foreach (glob($outputFolder . '/*') ?: [] as $file) { unlink($file); }
    if (is_dir($outputFolder)) { rmdir($outputFolder); }
}
