<?php

declare(strict_types=1);

namespace QRCodeWizard\Domain;

interface PayloadSource
{
    /** @return iterable<string> Exactly count() payloads, in generation order. */
    public function payloads(BatchOptions $options): iterable;
}
