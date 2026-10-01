<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use QRCodeWizard\Application\GenerateBatch;
use QRCodeWizard\Domain\BatchOptions;
use QRCodeWizard\Infrastructure\{EndroidSvgRenderer, InMemoryImageStore, SequentialPayloadSource};

$store = new InMemoryImageStore();
$generator = new GenerateBatch(new SequentialPayloadSource(), new EndroidSvgRenderer(), $store);
$result = $generator->generate(new BatchOptions(start: 7, count: 2));
echo 'Generated ' . $result->generatedCount() . " SVGs in memory.\n";
echo 'First image: ' . strlen($store->read('qr_00007.svg')) . " bytes.\n";
