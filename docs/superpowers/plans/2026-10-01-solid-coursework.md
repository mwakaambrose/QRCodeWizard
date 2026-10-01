# SOLID Coursework Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task if native execution is selected.

**Goal:** Preserve the QR generator's behavior while demonstrating SOLID and object-oriented APIs with verifiable coursework evidence.

**Architecture:** Domain value objects and narrow interfaces define contracts. An application service composes payload generation, rendering, and storage; adapters implement those contracts, and CLI entry points wire them together.

**Tech Stack:** PHP >=8.4, Composer, Endroid QR Code, PHPUnit, PHPStan.

**Spec:** ../specs/2026-10-01-solid-coursework-design.md

## Global Constraints

- PHP 8.4 or later; require GD for PNG output.
- Preserve 10,000 labeled PNGs, five-digit numbering, existing filenames, rendering settings, output directory, and success message by default.
- Work on refactor/solid-coursework; retain author and committer Mwaka Ambrose using the configured email; omit AI co-author trailers.
- At least four implementation commits in addition to the design/plan commits; no remote publication.
- Record real baseline and final results; declare AI assistance in coursework documentation independently of Git authorship.

## Review Focus

- Zero, negative, nonnumeric, and overflowing range values must fail before generating files (Task 2).
- Paths containing spaces and existing files at directory locations must behave predictably (Tasks 3 and 4).
- Unknown CLI options and missing values must produce help/error output and nonzero exits (Task 3).
- Alternate renderers must preserve payload and valid image-result contracts (Task 3).
- Failed storage writes must not truncate a prior output or leave temporary files (Task 4).

### Task 1: Characterize the original behavior

**Files:** composer.json, composer.lock, .gitignore, phpunit.xml, tests/Characterization/LegacyGeneratorTest.php, tests/Fixtures/legacy_generate_qrcodes.php, docs/evidence/baseline.md.

**Interfaces:** Preserve a verbatim legacy fixture for comparison. PHPUnit loads vendor/autoload.php. No production changes in this task.

- [ ] Add PHPUnit and PHPStan development dependencies, with versions compatible with PHP 8.4, and ignore vendor/, qrcodes/, and test caches.
- [ ] Write characterization tests for the original numbering, default range, renderer settings, and actual PNG output for 00001 and 10000. Use a temporary copy of the original script with the loop range limited by the test harness, recording that limitation.

```php
$number = str_pad(1, 5, '0', STR_PAD_LEFT);
self::assertSame('00001', $number);
self::assertStringContainsString('for ($i = 1; $i <= 10000; $i++)', $legacySource);
self::assertSame('image/png', getimagesize($samplePath)['mime']);
```

- [ ] Run PHPUnit against the unchanged production source; save the command, version, results, fixture hash, and representative PNG hashes. Record absence of pre-existing tests.
- [ ] Run a PHPStan baseline review of generate_qrcodes.php with a reproducible configuration and Composer audit --locked; record findings and exits without treating network failure as security success.
- [ ] Commit: `test: characterize legacy QR generation and capture baseline`.

### Task 2: Domain contracts and application API

**Files:** src/Domain/BatchOptions.php, src/Domain/QrImage.php, src/Domain/PayloadSource.php, src/Domain/QrRenderer.php, src/Domain/ImageStore.php, src/Application/GenerateBatch.php, src/Application/BatchResult.php, tests/Unit/BatchOptionsTest.php, tests/Unit/GenerateBatchTest.php, tests/Unit/QrImageTest.php, composer.json.

**Interfaces:** Namespace QRCodeWizard. `PayloadSource::payloads(BatchOptions $options): iterable` yields strings. `QrRenderer::render(string $payload): QrImage`. `ImageStore::save(QrImage $image): void` and `ImageStore::read(string $filename): string`. `GenerateBatch::__construct(PayloadSource $source, QrRenderer $renderer, ImageStore $store)`; `generate(BatchOptions $options): BatchResult`. BatchOptions encapsulates start/count, both defaulting to 1/10000 respectively. QrImage encapsulates a safe filename, MIME type, and nonempty bytes. BatchResult exposes generated count.

- [ ] Add PSR-4 autoloading and failing tests for missing types, constructor validation, dependency injection, and propagation of adapter failures.

```php
$options = new BatchOptions(start: 1, count: 2);
self::assertSame(2, $options->count());
$this->expectException(InvalidArgumentException::class);
new BatchOptions(start: PHP_INT_MAX, count: 2);
```

- [ ] Run the targeted tests and confirm they fail because the classes are absent.
- [ ] Implement private readonly value state and explicit accessors. Reject count/start below 1 and count greater than PHP_INT_MAX - start + 1. Reject filenames containing separators, traversal components, or empty values. Require nonempty image bytes and supported MIME types.
- [ ] Implement orchestration using only the three interfaces, saving one rendered image per yielded payload and counting successful writes. Do not catch failures and claim success.
- [ ] Run unit and characterization tests, then commit: `refactor: introduce encapsulated domain and SOLID application contracts`.

### Task 3: Adapters and public PHP/CLI APIs

**Files:** src/Infrastructure/SequentialPayloadSource.php, src/Infrastructure/EndroidPngRenderer.php, src/Infrastructure/EndroidSvgRenderer.php, src/Infrastructure/FilesystemImageStore.php, src/Infrastructure/InMemoryImageStore.php, src/Presentation/GenerateCommand.php, bin/generate, generate_qrcodes.php, examples/php-api.php, tests/Contract/RendererContractTest.php, tests/Contract/ImageStoreContractTest.php, tests/Integration/GenerateCommandTest.php, tests/Integration/OutputCompatibilityTest.php.

**Interfaces:** Adapters implement Task 2 contracts. `GenerateCommand::run(array $arguments): int` parses a list of arguments, composes adapters, reports results, and returns a process exit code. CLI options: --count, --start, --output, --format=png|svg, --help. Legacy entry point passes its arguments to the same command with the original default output location.

- [ ] Add failing renderer/store contract tests and PNG compatibility tests comparing bytes to the retained legacy renderer for representative payloads.

```php
$image = $renderer->render('00001');
self::assertSame('qr_00001.png', $image->filename());
$store->save($image);
self::assertSame($image->bytes(), $store->read($image->filename()));
```

- [ ] Implement lazy sequential numbering, Endroid adapters with all original settings, filesystem storage with checked failures, and in-memory storage. SVG is opt-in and does not alter default output.
- [ ] Add CLI tests covering valid PNG/SVG batches, output paths with spaces, invalid integers, unknown/missing options, and help. Confirm failures before writing parser implementation.
- [ ] Implement strict argument parsing, usage text, STDERR error reporting, nonzero exit codes, and the preserved default success message. Add PHP version/extension requirements to Composer metadata.
- [ ] Add runnable API example that injects SVG and in-memory adapters into the same service.
- [ ] Run all tests and small real CLI batches, then commit: `feat: expose CLI and PHP APIs with polymorphic QR adapters`.

### Task 4: Atomic storage and automated quality verification

**Files:** src/Infrastructure/FilesystemImageStore.php, tests/Integration/FilesystemSafetyTest.php, phpstan.neon, composer.json, docs/evidence/final.md.

**Interfaces:** Public save/read contracts remain unchanged. Temporary file creation and rename stay within the destination directory.

- [ ] Add failing tests proving directory/file conflicts fail clearly, unsafe filenames are rejected, missing reads fail, replacement succeeds, and a failed target rename preserves existing content and removes temporary files.

```php
mkdir($output . '/qr_00001.png');
try {
    $store->save($image);
    self::fail('Expected failed replacement');
} catch (RuntimeException $exception) {
    self::assertDirectoryExists($output . '/qr_00001.png');
    self::assertSame([], glob($output . '/.qr-*'));
}
```

- [ ] Implement checked temporary-file writes, complete byte-count verification, rename, and finally cleanup. Keep memory use proportional to one image.
- [ ] Configure PHPStan at its strictest practical level for src, bin, examples, and entry point; fix valid findings without suppressing whole categories.
- [ ] Run PHPUnit, PHPStan, Composer validate --strict, PHP syntax checks, and Composer audit --locked; save exact tool versions, configuration/scope, results, and limitations.
- [ ] Commit: `fix: make output writes atomic and verify quality contracts`.

### Task 5: Coursework documentation and delivery

**Files:** README.md, docs/coursework.md, docs/discussion-brief.md, docs/evidence/final.md.

**Interfaces:** Documentation uses actual final signatures and reproducible commands; no invented group identities, final hashes, tool findings, or screenshots.

- [ ] Explain installation, PHP/GD requirements, existing command, optional CLI arguments, PHP API, folder structure, and extension examples.
- [ ] Map Questions 1-4 to baseline code, SOLID/OOP changes, before/after test and tool results, atomic-write improvement, remaining limitations, and next improvement.
- [ ] Include two technical discussion questions, external library acknowledgment, and AI assistance declaration. Clarify that members must understand the implementation.
- [ ] Record repository URL, baseline hash, and short Git history; use `git rev-parse HEAD` as the reproducible final-commit command to avoid a self-referential hash.
- [ ] Check documented commands and examples against actual output; run the full checks once after the final code change.
- [ ] Commit: `docs: explain SOLID concepts and coursework evidence`.
- [ ] Verify at least four implementation commits, configured authorship on every commit, and clean working tree. Deliver branch name, checks, documentation links, and any material limitations.
