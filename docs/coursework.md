# CSC 3115: focused refactoring argument

## 1. Project background

Repository: `git@github.com:mwakaambrose/QRCodeWizard.git`.
Baseline: `65a17a2`. Refactor branch: `refactor/solid-coursework`.
Technologies: PHP 8.4+, Composer, Endroid QR Code, PHPUnit, PHPStan.
Selected component: `generate_qrcodes.php`, originally a 42-line batch script.

The selected feature generates labeled PNG QR codes for 00001–10000. It is useful
as a compact coursework project because one execution path demonstrates the cost
of mixing application decisions with infrastructure. The coursework values the
reasoning and evidence; the class count is not an improvement metric.

The PDF's administrative requirements are reference context. No group identities,
registration numbers, assigned presentation role, or paired-group baseline have been
supplied. Students must supply those details in their final submission. This document
is source material for the requested implementation, not a submitted PDF/report deck.

## 2. Question 1: one central code issue

**Issue:** the script simultaneously selects numbers, configures the QR library,
creates a directory, writes images, and reports success. It directly constructs the
PNG writer and Endroid builder. These are multiple reasons to change in one place,
illustrating SRP and dependency direction/DIP.

Original extract (the full original is retained in `tests/Fixtures`):

```php
for ($i = 1; $i <= 10000; $i++) {
    $number = str_pad($i, 5, '0', STR_PAD_LEFT);
    $builder = new Builder(writer: new PngWriter(), /* settings omitted */);
    $result = $builder->build();
    $result->saveToFile("{$outputDir}/qr_{$number}.png");
}
```

For example, adding SVG or using memory storage previously required modifying the
same script that controlled numbering and batch behavior. Testing range selection
also involved image rendering and filesystem effects. These are concrete costs of
coupling; a small script is not inherently bad, but this requested extension makes
its responsibility boundaries worth separating.

## 3. Question 2: preserve behavior and improve the design

**Chosen change:** introduce narrow contracts and constructor injection, with
private immutable options and image values. `GenerateBatch` now coordinates:

```php
foreach ($this->source->payloads($options) as $payload) {
    $this->store->save($this->renderer->render($payload));
    ++$count;
}
```

The service imports domain interfaces, not Endroid or filesystem classes. The CLI
selects concrete adapters. Numbering stays lazy and the original PNG settings have
one shared definition in `EndroidImageBuilder`.

| Concept | Concrete evidence | Why it matters |
| --- | --- | --- |
| SRP | Source, renderer, store, command, service | A format change does not require changing numbering |
| OCP | `QrRenderer`, `ImageStore` adapters | Supply SVG/memory via composition without editing service |
| LSP | `AdapterContractTest`, `GenerationTest` | Both renderers/stores satisfy shared success/failure guarantees |
| ISP | Separate `PayloadSource`, `QrRenderer`, `ImageStore` | Rendering clients need no numbering or persistence API |
| DIP | `GenerateBatch` constructor accepts interfaces | High-level flow is independent of the QR library and disk |
| Encapsulation | `BatchOptions`, `QrImage` private readonly state | Invalid ranges/unsafe filenames cannot be constructed |
| Abstraction | Interfaces describe observable behavior | Consumers need not know library internals |
| Polymorphism | Same service with PNG/SVG and memory/disk | Runtime substitutions preserve orchestration |
| Composition | Service receives collaborating objects | No inheritance used solely to demonstrate a keyword |

**Tests before refactoring:** 1 characterization test, 10 assertions on real PNG
output at the numbering boundaries. Production was unchanged at that stage.
**Tests after:** `composer test` reports 16 tests and 87 assertions. Comparisons
include byte equivalence with the original PNG at 00001, all default payloads, both renderers, both stores,
range validation, CLI behavior, and storage failure propagation.

The legacy characterization harness adapts only the autoload path and loop bounds
in a temporary copy. This limits workload while retaining the original builder and
write path. It does not prove a complete 10,000-file execution. Byte equivalence is
strong evidence for the tested sample, not proof for every possible QR payload.

## 4. Question 3: one meaningful automated finding

**Quality tool:** PHPStan 2.2.16, maximum level. Baseline scope was the original
script; final scope is `src`, CLI entry points, and the PHP example. Configuration:
`phpstan.neon`. The retained legacy fixture is deliberately excluded from final
analysis because it is unchanged evidence, not production code.

**Selected finding:** `argument.type` at original line 19: integer supplied to the
string parameter of `str_pad`. Original PHP weak typing coerced it and still ran.
This is a valid contract issue rather than a claimed runtime failure. Strict types
in the refactor require the explicit conversion:

```php
yield str_pad((string) ($options->start() + $offset), 5, '0', STR_PAD_LEFT);
```

**Decision:** fix it at the numbering boundary; do not suppress the diagnostic.
Baseline: one PHPStan error. Final: zero errors in the larger production scope.
This demonstrates a clearer contract, while output and range tests guard behavior.

**Other tools:** PHPUnit 12.5.37 verifies behavior; Composer 2.10.2 audit queries
known dependency advisories against the lock file. Audits include development tools.
Production dependency versions remain unchanged. Run `composer audit --locked`
to repeat the review. Zero advisories were reported at review time; this does not
establish complete application security.

There were no further quality findings to reject or defer. Deferred project work is
explained below rather than inventing additional warnings to increase the count.

## 5. Question 4: one remaining storage issue

After responsibility separation, a checked direct write could still follow an
existing filename symlink and overwrite its target. Interrupted writes could also
leave partial contents. This concerns a service's persistence contract and failure
safety: returning success should mean a complete image has been stored.

**Chosen improvement:** create a temporary file inside the output directory,
verify the complete byte count, rename it to the destination, and clean up in
`finally`. The service contract remains unchanged. Existing regular-file permission bits are
preserved; new files respect the process umask. Directory permissions default to
0755 rather than requesting world-writable permissions.

`FilesystemSafetyTest` was first run against direct writes: the symlink target was
changed from `keep` to `new`. The atomic implementation preserves `keep`, replaces
the link itself with the new image, and passes the test. A failed rename into an
existing directory preserves its contents and leaves no `.qr-*` temporary files.
These reproduce useful failure conditions without manufacturing dependency warnings.

Atomic rename prevents readers seeing a partly written target on ordinary local
filesystems. It is not a batch transaction or a durability guarantee after a power
failure, and no fsync is performed. The output directory must be controlled by the
caller; this code is not a hostile multi-user filesystem sandbox.

## 6. Reproduce the checks and inspect commits

```sh
composer install
composer test
composer analyse
vendor/bin/phpstan analyse tests/Fixtures/legacy_generate_qrcodes.php --level=max --debug --no-progress
composer validate --strict
composer audit --locked
git log --oneline 65a17a2..HEAD
```

The legacy characterization test runs the original script from `tests/Fixtures`
at the range boundaries. `GenerationTest` compares the original and refactored
PNG bytes for `00001`; run `composer test` to reproduce that comparison. The
original issue and its fix can also be inspected with `git show 65a17a2:generate_qrcodes.php`
and `src/Infrastructure/SequentialPayloadSource.php`.

Retrieve the final commit with `git rev-parse HEAD`. That command avoids embedding
an impossible self-referential final hash in its own commit. Commit boundaries show
baseline tests, domain refactor, polymorphic APIs, storage safety, and documentation.
All commits use Mwaka Ambrose's configured Git identity, with no AI co-author trailers.

## 7. Conclusion and limits

The preserved default output is supported by baseline characterization, the full
payload sequence test, a sample PNG byte comparison, and real small CLI batches.
The design makes format/storage changes local and demonstrates all requested OOP
concepts through one focused flow. It adds indirection; that tradeoff is justified
by actual alternate adapters and contract tests, not by the number of classes.

Remaining limitations: SVG output has no text label (Endroid writer limitation),
no QR decoding test, no full production-volume benchmark,
no resumable batches, no cross-batch transaction, and no untrusted-directory security
guarantee. Memory storage retains the batch in RAM and is intended for small API
examples/tests. The next improvement should be independent decoding of generated
PNGs to verify payload correctness; a benchmark should precede concurrency work.

The submitting students must reproduce the evidence and explain each boundary.
OpenAI Codex assisted with design, implementation, tests, and documentation. Endroid
and its dependencies supply QR encoding/rendering. Consult their upstream licenses
and include the AI-use declaration in the eventual report, as the brief requires.
