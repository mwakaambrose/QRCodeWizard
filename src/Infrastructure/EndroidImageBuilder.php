<?php

declare(strict_types=1);

namespace QRCodeWizard\Infrastructure;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\WriterInterface;
use InvalidArgumentException;
use QRCodeWizard\Domain\QrImage;

/** Internal adapter helper: one source of truth for legacy rendering settings. */
final class EndroidImageBuilder
{
    public static function build(string $payload, WriterInterface $writer, string $extension): QrImage
    {
        if (!preg_match('/\A[0-9]+\z/', $payload)) {
            throw new InvalidArgumentException('QR payload must be a nonempty decimal string.');
        }
        $result = (new Builder(
            writer: $writer,
            writerOptions: [],
            validateResult: false,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            labelText: $payload,
            labelFont: new OpenSans(16),
            labelAlignment: LabelAlignment::Center,
        ))->build();
        return new QrImage('qr_' . $payload . '.' . $extension, $result->getMimeType(), $result->getString());
    }
}
