<?php

declare(strict_types=1);

namespace QRCodeWizard\Domain;

use InvalidArgumentException;

final readonly class BatchOptions
{
    public function __construct(private int $start = 1, private int $count = 10000)
    {
        if ($start < 1 || $count < 1 || $count - 1 > PHP_INT_MAX - $start) {
            throw new InvalidArgumentException('Start/count must be positive and the range must fit a PHP integer.');
        }
    }

    public function start(): int { return $this->start; }
    public function count(): int { return $this->count; }
}
