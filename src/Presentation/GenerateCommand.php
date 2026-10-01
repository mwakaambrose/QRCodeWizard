<?php

declare(strict_types=1);

namespace QRCodeWizard\Presentation;

use InvalidArgumentException;
use QRCodeWizard\Application\GenerateBatch;
use QRCodeWizard\Domain\BatchOptions;
use QRCodeWizard\Infrastructure\{EndroidPngRenderer, EndroidSvgRenderer, FilesystemImageStore, SequentialPayloadSource};
use Throwable;

final readonly class GenerateCommand
{
    public function __construct(private string $defaultOutputDirectory) {}

    /** @param list<string> $arguments */
    public function run(array $arguments): int
    {
        try {
            if ($arguments === ['--help']) {
                echo "Usage: php bin/generate [--count=N] [--start=N] [--output=PATH] [--format=png|svg]\n";
                return 0;
            }
            $values = ['count' => '10000', 'start' => '1', 'output' => $this->defaultOutputDirectory, 'format' => 'png'];
            $seen = [];
            for ($index = 0; $index < count($arguments); ++$index) {
                $argument = $arguments[$index];
                if (!str_starts_with($argument, '--')) { throw new InvalidArgumentException('Expected an option, received: ' . $argument); }
                $parts = explode('=', substr($argument, 2), 2);
                $name = $parts[0];
                if (!array_key_exists($name, $values) || isset($seen[$name])) {
                    throw new InvalidArgumentException('Unknown or repeated option: --' . $name);
                }
                $value = $parts[1] ?? ($arguments[++$index] ?? '');
                if ($value === '' || str_starts_with($value, '--')) { throw new InvalidArgumentException('Missing value for --' . $name); }
                $values[$name] = $value;
                $seen[$name] = true;
            }
            $options = new BatchOptions($this->positiveInteger($values['start']), $this->positiveInteger($values['count']));
            $renderer = match ($values['format']) {
                'png' => new EndroidPngRenderer(),
                'svg' => new EndroidSvgRenderer(),
                default => throw new InvalidArgumentException('Format must be png or svg.'),
            };
            $service = new GenerateBatch(new SequentialPayloadSource(), $renderer, new FilesystemImageStore($values['output']));
            $result = $service->generate($options);
            echo '✅ Done! Generated ' . number_format($result->generatedCount()) . ' QR codes in: ' . $values['output'] . "\n";
            return 0;
        } catch (Throwable $exception) {
            fwrite(STDERR, 'Error: ' . $exception->getMessage() . "\n");
            return 1;
        }
    }

    private function positiveInteger(string $value): int
    {
        if (!preg_match('/\A[1-9][0-9]*\z/', $value)) { throw new InvalidArgumentException('Start/count must be positive decimal integers.'); }
        $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($parsed === false) { throw new InvalidArgumentException('Start/count exceeds the PHP integer range.'); }
        return $parsed;
    }
}
