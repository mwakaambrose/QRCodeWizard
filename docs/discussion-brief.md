# Discussion brief

Use this brief to prepare the paired-group discussion. Each member should first
complete the [explanation guide](member-explanation-guide.md). The full refactoring
argument and its limitations are in [coursework.md](coursework.md).

## Main argument

The original script mixed numbering, QR configuration, filesystem writes and
success reporting. The current design separates batch coordination, rendering and
saving through five production classes/interfaces. The benefit is that rendering
or saving can change without rewriting the batch workflow, while meaningful tests
check behavior. Fewer files make this design easier to explain; file count alone
does not prove quality.

The flow is:

```text
CLI options
  → QrCodeBatchGenerator::generateBatch()
  → QrCodeImageRendererInterface::renderQrCodeImage()
  → QrCodeImageSaverInterface::saveImageFile()
  → completed count and CLI success message
```

`Contracts/` contains the two interfaces, each named with an `Interface` suffix.
`Adapters/` contains their concrete implementations.
`EndroidQrCodeImageRenderer` implements the renderer interface;
`FolderQrCodeImageSaver` implements the saver interface. Endroid's `PngWriter` and
`SvgWriter` implement its `WriterInterface`, so the same renderer class can produce
either format. This is the concrete PNG/SVG polymorphism demonstration. The two
application interfaces also allow other implementations to be injected.

## Two questions for the paired group

1. Which evidence shows that your refactor preserved payload numbering, visual
   settings and filenames? Which tests compare observable behavior with the
   original, and which might merely mirror the implementation?
2. If a write fails halfway through a batch, what does the saving contract promise?
   How do you detect failed writes, can an interrupted overwrite leave partial
   contents, and do you promise atomic replacement or batch rollback?

## Points to be ready to explain

| Topic | Evidence to bring | Limit to acknowledge |
| --- | --- | --- |
| Preserved output | `LegacyGeneratorTest` and the original PNG byte comparison in `EndroidQrCodeImageRendererTest` | Samples do not prove all payloads or independently decode a QR image |
| Numbering | `QrCodeBatchGeneratorTest` checks the default sequence, boundaries and invalid ranges | The full sequence check does not render 10,000 images |
| Polymorphism | The same generator works with PNG/SVG writers in `EndroidQrCodeImageRendererTest` | SVG has no numeric text label; the writer and extension must match |
| Functional style | `$parsePositiveInteger` in `generate_qrcodes.php` is a function value that converts input without changing external state | This is a limited functional-style element, not a fully functional architecture |
| Structured programming | `generateBatch()` uses `if`, `for` and ordered format/render/save statements | Structured control flow can be used inside an object-oriented program |
| SOLID | Interfaces, constructor injection and the class/method PHPDoc | LSP requires behavior guarantees, not just an `implements` declaration |
| Storage safety | `FolderQrCodeImageSaverTest` covers symlink rejection, failed writes and normal permissions | Direct overwrites can leave partial files; earlier files remain after a later failure |
| Automated finding | Legacy PHPStan diagnostic for `str_pad` and explicit string conversion in the generator | The original weakly typed call still ran; this was a type-contract issue |
| Dependency review | Your own output from `composer audit:dependencies` | An advisory audit is not a complete security assessment |

## Follow-up prompts

- Which class changes when visual QR settings change? Which changes when files are saved elsewhere?
- Where are invalid CLI numbers, overflowing ranges and unsafe filenames rejected?
- What would a renderer that returns empty bytes or a saver that silently loses data violate?
- How would you add a compatible image format without editing the batch generator?
- Which statements demonstrate sequence, selection and iteration? What makes the parser a functional-style example, and what limits that claim?
- Why is independent QR decoding a useful next check? Why benchmark before adding concurrency?

Bring independently reproduced results and compare tradeoffs fairly. A different
folder structure is not evidence that another group's implementation is wrong.
Retain the Endroid acknowledgment and AI-assistance declaration in the final submission.
