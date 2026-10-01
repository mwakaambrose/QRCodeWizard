<?php

declare(strict_types=1);

namespace QRCodeWizard\Domain;

interface QrRenderer
{
    /** Render a nonempty decimal payload; return its named image or throw. */
    public function render(string $payload): QrImage;
}
