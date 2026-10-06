# Group member explanation guide

Every member should be able to run, trace and explain the complete program before
presentation responsibilities are divided. This guide uses the current five-file
design. Read the [coursework argument](coursework.md), then use the
[discussion brief](discussion-brief.md) to rehearse with the paired group.

## 1. Know what each file does

| File | Responsibility | Main methods |
| --- | --- | --- |
| `src/QrCodeBatchGenerator.php` | Validate the range and coordinate a numbered batch | `generateBatch(startNumber, numberOfCodes)` |
| `src/Contracts/QrCodeImageRendererInterface.php` | Define the image-rendering contract | `renderQrCodeImage(numberText)`, `getImageFileExtension()` |
| `src/Adapters/EndroidQrCodeImageRenderer.php` | Create image bytes using Endroid and the original visual settings | Implements both rendering methods |
| `src/Contracts/QrCodeImageSaverInterface.php` | Define the image-saving contract | `saveImageFile(fileName, imageBytes)` |
| `src/Adapters/FolderQrCodeImageSaver.php` | Validate filenames and write image files in a folder | Implements `saveImageFile()` |

`generate_qrcodes.php` handles CLI parsing, object construction, errors and output.
`bin/generate` launches that same script. A constructor supplies dependencies;
private readonly properties retain them. There are no separate options/image/result
classes. `Contracts/` holds the interfaces, and `Adapters/` holds the concrete
implementations. The batch generator stays directly under `src/`.

## 2. Trace a small batch

```sh
php bin/generate --start=7 --count=2 --output=/tmp/qr-practice
```

Follow this execution path in the source:

1. The entry script parses the options and validates canonical positive integers.
2. It selects `PngWriter`, constructs `EndroidQrCodeImageRenderer` with extension
   `png`, and constructs `FolderQrCodeImageSaver` for the output folder.
3. It injects these collaborators into `QrCodeBatchGenerator`.
4. `generateBatch(startNumber: 7, numberOfCodes: 2)` validates the range and formats
   `00007` and `00008`.
5. For each number, it creates `qr_00007.png` or `qr_00008.png`, asks
   `renderQrCodeImage()` for bytes, then calls `saveImageFile()`.
6. Only after both saves succeed does it return `2`; the entry script prints success.

Explain these variables: `$numberText` is encoded inside the QR image;
`$fileName` names the output; `$imageBytes` contains the encoded image contents.
The no-option default is 10,000 labeled PNGs numbered 00001–10000.

## 3. Locate polymorphism and SOLID

Run `php examples/php-api.php`. It generates five SVGs starting at 3, reads the
last image (`qr_00007.svg`) and cleans up its temporary output folder.

Polymorphism appears at two boundaries. The batch generator calls its injected
renderer and saver through their interfaces. Inside `EndroidQrCodeImageRenderer`,
Endroid's builder delegates writing to the injected `WriterInterface` object:
`PngWriter` and `SvgWriter` supply different implementations. The generator does
not branch on either concrete writer. Production includes one renderer adapter and
one saver adapter; alternate application implementations can use the same contracts.

| Principle | Explanation to give |
| --- | --- |
| SRP — Single Responsibility | Batch coordination, rendering, saving and CLI handling have separate reasons to change |
| OCP — Open/Closed | Inject another renderer or saver without rewriting the batch generator |
| LSP — Liskov Substitution | Replacements must honor valid inputs, output format and complete-save-or-throw behavior |
| ISP — Interface Segregation | A renderer needs no storage API; a saver needs no render/read/list/delete API |
| DIP — Dependency Inversion | The batch generator receives interfaces instead of constructing Endroid/filesystem dependencies |

Also explain encapsulation (private readonly collaborators), abstraction (the two
small interfaces) and composition (objects supplied through constructors). Read the
class and method PHPDoc for the concrete principle used at each point.

Do not claim PNG and SVG look identical: PNG includes a numeric label; SVG does not.
The PHP caller must supply an extension matching the injected writer.

## 4. Explain validation and failures

- CLI parsing rejects zero, negative, nonnumeric, overflowing and noncanonical integer strings, plus unknown/repeated options or missing values.
- `generateBatch()` rejects invalid/overflowing ranges for PHP callers too, before rendering or saving.
- `renderQrCodeImage()` accepts nonempty decimal strings; arbitrary text is outside this program's contract.
- `saveImageFile()` rejects unsafe filenames and empty bytes before creating the folder.
- Rendering/saving failures propagate to the PHP caller. The CLI reports them and exits with code 1.

A successful return means the whole requested loop completed. A later failure does
not remove earlier files or return a partial success count.

## 5. Explain simple file saving

Read `FolderQrCodeImageSaver::saveImageFile()` and its test file. The method validates
the filename and bytes, creates the output folder if needed, rejects an existing
filename symlink, and saves with `file_put_contents()`. It checks the byte count and
throws if the complete write fails. PHP/filesystem behavior handles permissions.

There are no temporary-file, rename or permission-copy steps. This keeps the saving
code small, but ordinary direct writes are not atomic: interruption or a short write
can leave a partial file. Existing filename symlinks are rejected, not replaced.
Already generated batch files remain after later failures. The caller must control
the output folder; this is not a concurrency or hostile-filesystem protection scheme.

## 6. Reproduce the evidence

```sh
composer test
composer analyse
composer validate --strict
composer audit:dependencies
```

| Test file | What each member should explain |
| --- | --- |
| `tests/LegacyGeneratorTest.php` | Original PNG output at the numbering boundaries |
| `tests/QrCodeBatchGeneratorTest.php` | Default sequence, integer boundary, invalid ranges and failure propagation |
| `tests/EndroidQrCodeImageRendererTest.php` | Real PNG/SVG batches, payload/extension validation and original PNG byte comparison |
| `tests/FolderQrCodeImageSaverTest.php` | File creation/replacement, symlink rejection, failed writes, permissions and invalid input |
| `tests/CliTest.php` | Help/errors and real small PNG/SVG batches in paths with spaces |

The numbering test avoids rendering 10,000 images. The original PNG comparison
checks a sample under the same library/runtime; it does not independently decode
the payload or prove every possible output.

Reproduce the original type finding separately:

```sh
vendor/bin/phpstan analyse tests/Fixtures/legacy_generate_qrcodes.php --level=max --debug --no-progress
```

That command is expected to report a diagnostic and exit nonzero. The original
script passed an integer to `str_pad`; the current generator explicitly casts to a
string. Weak typing allowed the original to run, but the type contract was wrong.
An audit reports known dependency advisories; it does not prove total security.

## 7. Practice explanations

- **Where does Endroid enter?** In the concrete renderer; the entry script chooses the writer. The batch generator knows only the application interfaces.
- **What changes for another output format?** Supply a compatible writer and matching extension, or implement `QrCodeImageRendererInterface`; add behavior checks. The batch generator remains unchanged. Exposing a new CLI format also requires updating its format choices.
- **What changes for another storage destination?** Implement `QrCodeImageSaverInterface` and inject it. Preserve complete-save-or-throw behavior.
- **Why only five production files?** These boundaries separate meaningful responsibilities without extra payload/options/result wrappers or layers.
- **What is the next useful check?** Independently decode sample QR images; benchmark before optimizing throughput.

Each member should demonstrate one successful run, explain one failure case, point
to every SOLID principle, and state one evidence limitation. Students must supply
group names, registration numbers, presentation roles and paired-group details.
Keep the Endroid attribution and AI-assistance declaration in the final report.
