<?php

declare(strict_types=1);

namespace QRCodeWizard\Domain;

interface ImageStore
{
    /** Replace an image; return only after success, otherwise throw. */
    public function save(QrImage $image): void;
    /** Return stored bytes for a safe filename; throw if missing. */
    public function read(string $filename): string;
}
