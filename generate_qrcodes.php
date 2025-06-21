<?php

require 'vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

$outputDir = __DIR__ . '/qrcodes';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

for ($i = 1; $i <= 10000; $i++) {
    $number = str_pad($i, 5, '0', STR_PAD_LEFT); // e.g. 00001

    $builder = new Builder(
        writer: new PngWriter(),
        writerOptions: [],
        validateResult: false,
        data: $number,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 300,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
        labelText: $number,
        labelFont: new OpenSans(16), // You can reduce font size if it overlaps
        labelAlignment: LabelAlignment::Center
    );

    $result = $builder->build();

    $filePath = "{$outputDir}/qr_{$number}.png";
    $result->saveToFile($filePath);
}

echo "✅ Done! Generated 10,000 QR codes in: {$outputDir}\n";
