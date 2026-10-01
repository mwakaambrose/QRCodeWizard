<?php

declare(strict_types=1);

namespace QRCodeWizard\Infrastructure;

use QRCodeWizard\Domain\{BatchOptions, PayloadSource};

final class SequentialPayloadSource implements PayloadSource
{
    public function payloads(BatchOptions $options): iterable
    {
        for ($offset = 0; $offset < $options->count(); ++$offset) {
            yield str_pad((string) ($options->start() + $offset), 5, '0', STR_PAD_LEFT);
        }
    }
}
