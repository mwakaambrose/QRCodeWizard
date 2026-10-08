<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Writer\{PngWriter, SvgWriter};
use QRCodeWizard\QrCodeBatchGenerator;
use QRCodeWizard\Adapters\{EndroidQrCodeImageRenderer, FolderQrCodeImageSaver};

$arguments = array_slice($argv, 1);
/**
 * Functional style: a function stored in a variable transforms input without
 * changing external state. Invalid input throws instead of returning a value.
 */
$parsePositiveInteger = static function (string $optionValue): int {
    if (!preg_match('/\A[1-9][0-9]*\z/', $optionValue)) {
        throw new InvalidArgumentException('Start/count must be positive decimal integers.');
    }
    $parsedInteger = filter_var($optionValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($parsedInteger === false) {
        throw new InvalidArgumentException('Start/count exceeds the PHP integer range.');
    }
    return $parsedInteger;
};

try {
    if ($arguments === ['--help']) {
        echo "Usage: php bin/generate [--count=N] [--start=N] [--output=PATH] [--format=png|svg]\n";
        exit(0);
    }
    $optionValues = ['count' => '10000', 'start' => '1', 'output' => __DIR__ . '/qrcodes', 'format' => 'png'];
    $processedOptionNames = [];
    for ($index = 0; $index < count($arguments); ++$index) {
        $argument = $arguments[$index];
        if (!str_starts_with($argument, '--')) {
            throw new InvalidArgumentException('Expected an option, received: ' . $argument);
        }
        $optionParts = explode('=', substr($argument, 2), 2);
        $optionName = $optionParts[0];
        if (!array_key_exists($optionName, $optionValues) || isset($processedOptionNames[$optionName])) {
            throw new InvalidArgumentException('Unknown or repeated option: --' . $optionName);
        }
        $optionValue = $optionParts[1] ?? ($arguments[++$index] ?? '');
        if ($optionValue === '' || str_starts_with($optionValue, '--')) {
            throw new InvalidArgumentException('Missing value for --' . $optionName);
        }
        $optionValues[$optionName] = $optionValue;
        $processedOptionNames[$optionName] = true;
    }
    $startNumber = $parsePositiveInteger($optionValues['start']);
    $numberOfCodes = $parsePositiveInteger($optionValues['count']);
    $imageRenderer = match ($optionValues['format']) {
        'png' => new EndroidQrCodeImageRenderer(new PngWriter(), 'png'),
        'svg' => new EndroidQrCodeImageRenderer(new SvgWriter(), 'svg'),
        default => throw new InvalidArgumentException('Format must be png or svg.'),
    };
    $imageSaver = new FolderQrCodeImageSaver($optionValues['output']);
    $batchGenerator = new QrCodeBatchGenerator($imageRenderer, $imageSaver);
    $generatedCount = $batchGenerator->generateBatch(
        startNumber: $startNumber,
        numberOfCodes: $numberOfCodes,
    );
    echo '✅ Done! Generated ' . number_format($generatedCount) . ' QR codes in: ' . $optionValues['output'] . "\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . "\n");
    exit(1);
}
