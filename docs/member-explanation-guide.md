# Member explanation guide

Each member should complete the following walkthrough independently. Divide
presentation sections only after everyone can explain the complete execution path.

1. Run `php bin/generate --start=7 --count=2 --output=/tmp/qr-practice`. Trace
   argument parsing → BatchOptions → GenerateBatch → SequentialPayloadSource →
   EndroidPngRenderer → QrImage → FilesystemImageStore → BatchResult.
2. Explain why negative counts and overflowing ranges fail before writing images.
   Private readonly values preserve those invariants after construction.
3. Run `php examples/php-api.php`. Explain why replacing PNG/disk with SVG/memory
   requires no changes to GenerateBatch: interfaces, polymorphism, OCP, and DIP.
4. Point to concrete code for each SOLID principle. Explain what changes if a
   renderer changes format or a store becomes remote. Do not equate LSP with merely
   implementing an interface: behavior and failure contracts also need to agree.
5. Read `AdapterContractTest`. Explain how the same expectations apply to each
   implementation and how a store that silently discards data would fail readback.
6. Run `composer test` and `composer analyse`. Find the legacy str_pad diagnostic
   in baseline evidence and the explicit conversion in SequentialPayloadSource.
   Explain why original weak typing worked and why the warning is still valid.
7. Read `FilesystemSafetyTest`. Draw the symlink/target relationship and explain why
   direct writes alter the target, while rename replaces the output directory entry.
8. Explain atomic per-file writes versus an all-or-nothing batch. Explain why
   generation stops on an exception and why already generated images remain.
9. Reproduce `composer audit:dependencies`. Explain the difference between known
   dependency advisories and a complete security assessment.
10. Defend the tradeoff: extra boundaries are useful because alternate implementations
    actually exist, while a framework, artificial inheritance, and concurrent workers
    would add complexity without evidence of need.

Practice prompts with expected reasoning:

- **Where does Endroid enter the system?** Infrastructure adapters, selected by the
  presentation/composition boundary; application code imports only domain contracts.
- **Can a renderer accept arbitrary text?** This API guarantees decimal payloads;
  both adapters reject other inputs consistently. A broader payload contract would
  require changing safe naming independently of the QR encoding capability.
- **What would a new JPEG renderer require?** Define/support its MIME and filename
  representation in the image value contract, implement the renderer, and add
  contract tests. The generation service stays unchanged. OCP is contextual, not a
  promise that every future feature can be added without any existing-file edits.
- **What does the PNG comparison prove?** Exact sample preservation under the same
  library/runtime, not all payloads or independent decoding correctness.
- **What is the next justified improvement?** Decode sample PNGs independently to
  verify content; benchmark before optimizing throughput.

Group names, registration numbers, presentation role, and paired-group details
must be supplied by the students. No member should present a section they cannot
run, trace, and defend. Keep the AI assistance declaration in the final report.
