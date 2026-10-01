<?php

declare(strict_types=1);

namespace QRCodeWizard\Application;

use QRCodeWizard\Domain\{BatchOptions, ImageStore, PayloadSource, QrRenderer};

final readonly class GenerateBatch
{
    public function __construct(private PayloadSource $source, private QrRenderer $renderer, private ImageStore $store) {}

    public function generate(BatchOptions $options): BatchResult
    {
        $count = 0;
        foreach ($this->source->payloads($options) as $payload) {
            $this->store->save($this->renderer->render($payload));
            ++$count;
        }
        return new BatchResult($count);
    }
}
