# QRCodeWizard

A PHP 8.4+ batch QR generator refactored for CSC 3115. The default command still
creates 10,000 labeled PNGs, numbered 00001–10000, in `qrcodes/`.

## Run

Requires PHP >=8.4, GD, and Composer. Development checks also need the extensions
required by PHPUnit (Composer verifies them).

```sh
composer install
php generate_qrcodes.php
# Small batch; avoids generating all 10,000 images during exploration:
php bin/generate --start=7 --count=2 --output="/tmp/my qr codes"
php bin/generate --count=2 --format=svg --output=/tmp/qr-svg
php bin/generate --help
php examples/php-api.php
```

Options accept `--name=value` or `--name value`. Start/count must be positive
canonical decimal integers, and the range must fit a PHP integer. Formats are PNG
and SVG. Paths may contain spaces. Unknown/repeated options, missing values, and
write errors return exit code 1; success/help return 0. Existing filenames are
replaced. Choose a dedicated output directory that you control. Earlier images in
a batch remain if a later image fails; there is no batch transaction.

## Public PHP API

```php
require 'vendor/autoload.php';

use QRCodeWizard\Application\GenerateBatch;
use QRCodeWizard\Domain\BatchOptions;
use QRCodeWizard\Infrastructure\{
    SequentialPayloadSource, EndroidPngRenderer, FilesystemImageStore
};

$generator = new GenerateBatch(
    new SequentialPayloadSource(),
    new EndroidPngRenderer(),
    new FilesystemImageStore(__DIR__ . '/qrcodes'),
);
$result = $generator->generate(new BatchOptions(start: 1, count: 3));
echo $result->generatedCount(); // 3
```

Replace the renderer with `EndroidSvgRenderer` or the store with
`InMemoryImageStore` without changing `GenerateBatch`. The runnable
[PHP example](examples/php-api.php) demonstrates both replacements. The PHP API
throws on invalid input or failures, allowing callers to choose how to handle them.
The CLI converts these exceptions into error messages and exit codes.

## Structure and concepts

| Folder | Responsibility |
| --- | --- |
| `src/Domain` | Immutable validated options/images; narrow interfaces |
| `src/Application` | Batch orchestration and result |
| `src/Infrastructure` | Endroid, sequential numbering, disk and memory adapters |
| `src/Presentation` | CLI parsing, composition, errors and success output |
| `bin`, `examples` | Executable CLI and public API demonstration |
| `tests` | Legacy characterization, unit, contract and integration tests |
| `docs` | Coursework argument, evidence and explanation guide |

SRP separates rendering, numbering, persistence, and presentation. OCP allows adapter
extensions. LSP is demonstrated with shared renderer/store contracts and tests. ISP
keeps numbering, rendering, and storage capabilities separate. DIP makes the
application depend on interfaces and receive adapters through its constructor.
Private readonly state demonstrates encapsulation; interfaces provide abstraction;
interchangeable adapters demonstrate polymorphism; the service demonstrates
composition. There is no artificial inheritance hierarchy.

## Verify

```sh
composer test
composer analyse
composer validate --strict
composer audit:dependencies
```

PHPStan runs at maximum level, using `--debug` to avoid restricted worker sockets.
Tests use temporary directories and small real renders; the default 10,000-payload
sequence is tested without writing 10,000 files. See [evidence](docs/evidence/baseline.md)
and [coursework](docs/coursework.md) for scope and limitations.

## Study and attribution

Read the [member explanation guide](docs/member-explanation-guide.md) and
[discussion brief](docs/discussion-brief.md). Every member should understand the
implementation and reproduce its evidence before presenting it.

QR rendering uses [Endroid QR Code](https://github.com/endroid/qr-code) and its
transitive dependencies; their licenses remain in Composer/vendor metadata. This
refactor and its documentation were developed with OpenAI Codex assistance, with
review and explanation required from the submitting students. Git commits use the
repository owner's identity, as requested; this does not remove the AI declaration.
The application has no new license grant; agree a license before redistributing it.
