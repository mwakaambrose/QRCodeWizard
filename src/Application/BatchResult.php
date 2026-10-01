<?php

declare(strict_types=1);

namespace QRCodeWizard\Application;

final readonly class BatchResult
{
    public function __construct(private int $generatedCount) {}
    public function generatedCount(): int { return $this->generatedCount; }
}
