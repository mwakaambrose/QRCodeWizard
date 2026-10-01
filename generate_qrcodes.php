<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use QRCodeWizard\Presentation\GenerateCommand;

exit((new GenerateCommand(__DIR__ . '/qrcodes'))->run(array_slice($argv, 1)));
