# QRCodeWizard coursework design

## Intent and scope

Refactor the existing QR batch generator to demonstrate all five SOLID principles,
encapsulation, interfaces, abstraction, composition, and polymorphism on PHP 8.4
or later. The user requested a new branch, a clear folder structure, user-facing
APIs, and at least four implementation commits. The coursework PDF is a source of
assessment requirements, not an independent instruction to submit or publish work.

Repository: git@github.com:mwakaambrose/QRCodeWizard.git. Baseline: 65a17a2.
Branch: refactor/solid-coursework. No remote publication is in scope.

## Preserved behavior

The existing command remains supported. With no arguments it generates 10,000
PNG files in the project's qrcodes directory, named qr_00001.png through
qr_10000.png. Each QR payload and label is the five-digit number. Preserve UTF-8,
high error correction, size 300, margin 10, margin block rounding, centered labels,
and OpenSans at size 16. Preserve the success message after a successful run.
Successful output equivalence is verified against the installed original renderer.
Errors will become explicit failures instead of unchecked filesystem warnings.

## Public APIs and layout

- src/Domain: validated immutable batch options, QR image value objects, and small
  contracts for payload production, rendering, and storage.
- src/Application: batch generation orchestration and a result containing the
  number of images written. Dependencies arrive through constructor injection.
- src/Infrastructure: sequential payload producer, Endroid PNG renderer, SVG
  renderer, filesystem storage, and in-memory storage for demos and tests.
- src/Presentation: CLI argument parsing and exit-code/error presentation.
- bin/generate: explicit CLI composition root.
- generate_qrcodes.php: backwards-compatible entry point delegating to the CLI.
- examples: runnable PHP API usage showing interchangeable implementations.
- tests: characterization, contract, unit, and small real-output integration tests.
- docs: coursework analysis, evidence, reproduction commands, and discussion brief.

The public PHP API accepts validated options and injected implementations. The CLI
offers count, start, output directory, and format options while keeping PNG defaults.
"User-facing APIs" is interpreted as PHP and CLI APIs within the existing local
generator; a web service would introduce a separate subsystem and is outside this
refactor's scope.

## Object-oriented concepts

SRP: options validate, producers supply payloads, renderers create images, stores
persist images, orchestration coordinates, and presentation handles CLI interaction.
OCP: a new renderer or store can be supplied without changing orchestration.
LSP: renderer and store contract tests run against every supplied implementation;
PNG and SVG share image-result guarantees and stores share readback guarantees.
ISP: clients receive small capability-specific interfaces, not a combined service.
DIP: application code depends on contracts; the entry point chooses concrete adapters.
Encapsulation: private immutable state is exposed through explicit accessors; invalid
options and unsafe image filenames cannot be constructed. Polymorphism: the same
generation service uses either renderer and either store. Composition is preferred
over artificial inheritance; interfaces supply abstraction without unnecessary bases.

## Error handling and final improvement

Validate positive count/start, non-empty paths, and bounded integer arithmetic.
Generate payloads lazily rather than retaining an entire batch in memory. Check
directory creation and writes and report actionable failures with a nonzero exit.
Reject path traversal through generated filenames. Filesystem writes use a temporary
file in the destination directory followed by rename, so failed writes cannot leave
a partially written QR file. This is the proposed Question 4 improvement, verified
with storage failure tests. Existing output files remain replaceable as before.

## Verification and coursework evidence

Before refactoring, add characterization tests for the original builder settings,
payload range, filenames, default command behavior, and representative PNGs. Record
baseline results before changing implementation. Do not generate a 10,000-file batch
in the repository merely to test it; use temporary output and representative renders.

Use PHPStan as the code-quality tool, PHPUnit as the testing tool, and Composer audit
as the dependency security tool. Record versions, configuration, scope, commands,
exit codes, and actual findings before and after. Do not invent findings or claim an
unavailable network audit passed. Run PHP syntax checks and Composer validation.

The README explains setup, CLI and PHP APIs, tests, and SOLID examples. The coursework
document maps Questions 1-4 to code and evidence, records the baseline and commit
history, explains accepted/deferred findings, acknowledges Endroid and AI assistance,
and describes limitations. Group identities and registration numbers are not supplied
and will not be fabricated. Formal report/slide artifacts are not requested in this
implementation task; source evidence and a discussion brief support their preparation.

## Commit boundaries

Keep the design commit separate from at least four implementation commits:
1. Characterization tests and recorded baseline evidence.
2. PHP 8.4 requirements, domain contracts, and application orchestration.
3. Rendering/storage adapters, public CLI/PHP APIs, and polymorphism examples.
4. Atomic storage improvement, automated quality checks, documentation, and final
   evidence (split further if needed).

## Acceptance criteria

All supplied implementations satisfy shared contracts; tests show representative
PNG equivalence and preserved defaults; CLI and PHP examples work on PHP 8.4;
quality and security results are recorded truthfully; at least four implementation
commits exist on the new branch; working tree is clean when delivered.
