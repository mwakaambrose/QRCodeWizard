# QRCodeWizard

A PHP 8.4+ batch QR generator with five production classes/interfaces. The default
command creates 10,000 labeled PNGs, numbered 00001–10000, in `qrcodes/`.

## Run

Requires PHP >=8.4, GD, SimpleXML and Composer. Composer checks the additional
extensions needed by development tools.

```sh
composer install
php generate_qrcodes.php
# Use small batches while exploring:
php bin/generate --start=7 --count=2 --output="/tmp/my qr codes"
php bin/generate --count=2 --format=svg --output=/tmp/qr-svg
php bin/generate --help
php examples/php-api.php
```

Options accept `--name=value` or `--name value`. Start/count must be positive
canonical decimal integers, and the range must fit a PHP integer. PNG includes a
numeric label; Endroid SVG contains the QR symbol without a text label. Unknown or
repeated options, missing values and write failures return exit code 1;
success/help return 0. Paths may contain spaces. Existing filenames are replaced.
Saving uses a checked `file_put_contents()` call and rejects existing filename
symlinks. Direct overwrites are not atomic and can leave partial files if interrupted.
Earlier files remain if a later image fails; there is no batch transaction.

## PHP API

```php
require 'vendor/autoload.php';

use Endroid\QrCode\Writer\PngWriter;
use QRCodeWizard\QrCodeBatchGenerator;
use QRCodeWizard\Adapters\{EndroidQrCodeImageRenderer, FolderQrCodeImageSaver};

$batchGenerator = new QrCodeBatchGenerator(
    new EndroidQrCodeImageRenderer(new PngWriter(), 'png'),
    new FolderQrCodeImageSaver(__DIR__ . '/qrcodes'),
);
$generatedCount = $batchGenerator->generateBatch(startNumber: 1, numberOfCodes: 3);
echo $generatedCount; // 3
```

For SVG, inject `new SvgWriter()` with the `'svg'` extension. Supply the extension
that matches the writer. The [PHP example](examples/php-api.php) generates five
SVGs starting at 3 in a temporary directory and cleans them up.

The generator accepts any `QrCodeImageRendererInterface` and `QrCodeImageSaverInterface` implementation. A renderer
returns image bytes and its filename extension; a store saves a filename and bytes.
Failures throw exceptions to the PHP caller. The CLI reports them with a nonzero exit.
This replaces the previous layered PHP API; callers must update their imports and
pass startNumber/numberOfCodes directly instead of options/result wrappers.

## Structure

```text
src/
  QrCodeBatchGenerator.php
  Contracts/
    QrCodeImageRendererInterface.php
    QrCodeImageSaverInterface.php
  Adapters/
    EndroidQrCodeImageRenderer.php
    FolderQrCodeImageSaver.php
```

`Contracts/` contains interfaces that define the required capabilities.
`Adapters/` contains concrete implementations that use Endroid and the filesystem.
The `Interface` suffix makes each contract recognizable in code.

`generate_qrcodes.php` parses CLI arguments and wires the objects together;
`bin/generate` launches the same script. `tests/` contains behavior tests and the
original script fixture. `docs/` contains the coursework argument, member explanation
guide and paired-group discussion brief.

The program follows this sequence:

1. The entry script reads the CLI options and chooses PNG or SVG.
2. `generateBatch(startNumber, numberOfCodes)` validates the range and formats each number.
3. `renderQrCodeImage(numberText)` creates the image bytes.
4. `saveImageFile(fileName, imageBytes)` writes the completed image to the output folder.
5. The batch returns its completed count; the entry script prints the result.

Rendering, persistence and batch coordination have separate responsibilities (SRP).
Injected renderer/store interfaces allow replacements without editing the generator
(OCP/DIP), with narrow capabilities (ISP) and shared behavior/failure contracts (LSP).
Private readonly dependencies demonstrate encapsulation; interfaces and Endroid's
interchangeable PNG/SVG writers provide abstraction and polymorphism through composition.

## Verify

```sh
composer test
composer analyse
composer validate --strict
composer audit:dependencies
```

PHPStan uses its maximum level and `--debug` to avoid restricted worker sockets.
Tests use temporary directories, small real renders and a retained legacy fixture.
The default 10,000-number sequence is checked without writing 10,000 images.
See [coursework notes](docs/coursework.md) for evidence and limitations.

## Study and discussion

Read the documents in this order:

1. [Group member explanation guide](docs/member-explanation-guide.md): the five files,
   complete program flow, polymorphism, SOLID, validation, simple file saving and test walkthrough.
2. [Coursework notes](docs/coursework.md): the original issue, refactoring argument,
   automated finding, storage improvement and evidence limitations.
3. [Discussion brief](docs/discussion-brief.md): paired-group questions, evidence to
   bring and follow-up prompts.

Every member should independently run the program and checks, trace a generated
image, and explain both a failure case and the limits of the evidence before presenting.

## Attribution

QR encoding/rendering uses [Endroid QR Code](https://github.com/endroid/qr-code)
and its dependencies; consult their licenses in Composer/vendor metadata.
OpenAI Codex assisted with design, implementation, tests and documentation.
Students must understand and reproduce the work before presenting it and retain
that declaration in their final report. The application has no new license grant;
agree a license before redistributing it.
