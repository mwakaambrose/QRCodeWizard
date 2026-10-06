# CSC 3115: focused SOLID refactoring

## Background and original plan

Repository: `git@github.com:mwakaambrose/QRCodeWizard.git`.
Original baseline: `65a17a2`; branch: `refactor/solid-coursework`.
Technologies: PHP 8.4+, Composer, Endroid QR Code, PHPUnit and PHPStan.
The original generator was a 42-line batch script.

The October 1 request asked for all five SOLID principles, encapsulation,
polymorphism, interfaces, local user-facing APIs and at least four implementation
commits. The design at `a18a09d` and plan at `dd1afc4` prescribed four architectural
layers and additional value objects/adapters. The user subsequently requested a
simpler design and selected exactly five production class/interface files.

Recover the original planning documents without adding them to the project:

```sh
git show a18a09d:docs/superpowers/specs/2026-10-01-solid-coursework-design.md
git show dd1afc4:docs/superpowers/plans/2026-10-01-solid-coursework.md
```

Class count is not an improvement metric. The useful boundaries are rendering,
persistence and batch coordination; the simplified version retains those boundaries
and removes the four-layer structure, payload abstraction and value/result wrappers.
`src/Contracts/` now identifies interfaces explicitly, while `src/Adapters/` contains
the concrete Endroid and folder-saving implementations.
The CLI and PHP API remain local; there is no HTTP service.

## Question 1: one central issue

The original script selected numbers, configured Endroid, created an output directory,
wrote images and printed success. Direct construction of the PNG builder coupled
batch decisions to rendering and filesystem effects. Testing numbering required
rendering/writes, and alternate formats required changing the same execution flow.
This is a concrete SRP/dependency-direction issue, rather than evidence that a small
script is inherently wrong.

The original is retained verbatim in `tests/Fixtures/legacy_generate_qrcodes.php`.

## Question 2: preserve behavior with fewer boundaries

`QrCodeBatchGenerator` validates start/count, formats each decimal number and coordinates:

```php
$fileName = 'qr_' . $numberText . '.' . $imageFileExtension;
$imageBytes = $this->imageRenderer->renderQrCodeImage($numberText);
$this->imageSaver->saveImageFile($fileName, $imageBytes);
```

It accepts two injected interfaces and returns the number successfully generated
as an integer. `EndroidQrCodeImageRenderer` keeps the original builder settings in one place
and accepts Endroid's existing writer interface. `FolderQrCodeImageSaver` owns safe
filenames and checked direct file writes. CLI parsing/composition lives in the existing
entry script rather than another class.

| Concept | Evidence and meaning |
| --- | --- |
| SRP | Generator coordinates; renderer builds bytes; store persists; script handles CLI |
| OCP | Alternate renderer/store implementations can be injected without changing generator |
| LSP | Renderers return valid bytes for decimal payloads; stores succeed completely or throw |
| ISP | Renderer exposes renderQrCodeImage/getImageFileExtension; saver exposes only saveImageFile |
| DIP | Generator depends on renderer/store interfaces, not Endroid or filesystem classes |
| Encapsulation | Private readonly dependencies; validation at generation and persistence boundaries |
| Abstraction | Two small interfaces describe the required behavior |
| Polymorphism | The same generator uses PNG/SVG through interchangeable Endroid writers |
| Composition | Dependencies are supplied through constructors; no artificial inheritance |

The renderer's supplied extension must match its writer; the CLI supplies the
correct pair. Filesystem storage accepts safe lowercase alphanumeric extensions,
so adding a renderer does not require widening a hardcoded PNG/SVG filename list.
The old PHP API intentionally changes: import the generator from `QRCodeWizard`,
interfaces from `QRCodeWizard\Contracts` and implementations from
`QRCodeWizard\Adapters`; pass
startNumber/numberOfCodes directly, and consume an integer rather than an options/result wrapper.

Verification covers the full default numbering sequence without 10,000 writes,
integer boundaries, failure propagation, small real PNG/SVG batches, CLI parsing,
and filesystem behavior. A PNG byte comparison against the original verifies the
sample at 00001. Legacy characterization also renders 00001 and 10000.

Historical baseline before the original refactor: one characterization test with
10 assertions; immediately before simplification: 16 tests and 87 assertions.
Run `composer test` for the current result. Temporary legacy copies alter only the
autoload location and loop bounds. Sample equivalence does not prove every payload
or an entire 10,000-image run.

## Question 3: one meaningful automated finding

PHPStan at its maximum level found an integer passed to the string parameter of
`str_pad` in the original script. PHP's weak typing allowed it to run, but the call
violated the declared type contract. `QrCodeBatchGenerator` explicitly casts the number to
a string before padding; the finding is fixed rather than suppressed.

The final PHPStan scope includes `src`, both entry points and the PHP example.
The unchanged legacy fixture is excluded from final analysis. Its original finding
can be reproduced separately:

```sh
vendor/bin/phpstan analyse tests/Fixtures/legacy_generate_qrcodes.php --level=max --debug --no-progress
```

This command is expected to report the baseline finding and exit nonzero.
Composer audit checks known dependency advisories, including development packages.
Past zero-advisory results are historical; run the audit again for a current result.
A dependency audit does not establish complete application security.

## Question 4: keep saving simple and check failures

The original script saved images directly. The current folder saver keeps that
simple approach: validate the filename/content, create the output folder if needed,
then call `file_put_contents()` and check that all bytes were written.

An existing filename symlink is rejected rather than followed or replaced. Invalid
filenames and empty bytes fail before folder creation. Directory/write failures
throw exceptions instead of allowing the program to print a misleading success
message. Tests cover normal creation/replacement, symlink rejection, directory
conflicts, invalid input and normal filesystem permissions.

The earlier atomic-write implementation was removed at the user's request to keep
the program easier to understand. Writes now use ordinary filesystem behavior,
without temporary files, permission copying or rename/cleanup steps. An interrupted
or incomplete overwrite can leave a partial file; there is no atomic replacement,
batch rollback or durability guarantee. Earlier batch files remain after a later
failure. The caller must control the output folder; the symlink check is not a
concurrent or hostile multi-user filesystem guarantee.

## Reproduce and explain

```sh
composer install
composer test
composer analyse
composer validate --strict
composer audit:dependencies
php bin/generate --start=7 --count=2 --output=/tmp/qr-practice
php examples/php-api.php
git log --oneline 65a17a2..HEAD
git rev-parse HEAD
```

For the complete execution trace, file responsibilities, SOLID/polymorphism
explanations and test walkthrough, use the
[group member explanation guide](member-explanation-guide.md).
Every member should independently reproduce the checks and explain the flow.

Use the [discussion brief](discussion-brief.md) to prepare the paired-group
questions and bring evidence with its limitations. These supporting documents
use the current five-file API and method names.

## Limits and attribution

SVG has no numeric text label. There is no independent decoding test, complete
production-volume benchmark, resumability, concurrency or batch transaction.
Independent PNG decoding is a useful next verification step; benchmark before
optimizing throughput. Keep each boundary only when its behavior earns the cost.

Students must supply group names, registration numbers, presentation role and
paired-group details; none are invented here. Every member must understand and
reproduce the work. These notes are source material, not a submitted report/deck.
Endroid and its dependencies provide QR rendering; consult their upstream licenses.
OpenAI Codex assisted with design, implementation, tests and documentation.
Keep the AI-use declaration in the final submission independently of Git authorship.
