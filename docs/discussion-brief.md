# Discussion brief

The central argument is that one small batch script combined unrelated reasons to
change and depended directly on infrastructure. The refactor's benefit is proven
by substituting rendering/storage adapters and preserving sample output, not by
counting classes. Discuss the value and cost of those boundaries.

Two technical questions for the paired group:

1. Which evidence proves your refactor preserved QR payloads, visual settings, and
   filenames? Does a passing test verify original behavior or merely mirror your
   implementation? Explain the limitations of sample comparisons.
2. If an output write fails halfway through a batch, what does your storage contract
   promise? How do you prevent partial file contents, and do you promise batch-level
   rollback or only per-file replacement?

Additional prompts: What LSP guarantees survive a PNG→SVG substitution? Where are
invalid inputs rejected? Which PHPStan finding mattered, and why? What costs would
concurrency introduce before benchmarking? Why keep the API local rather than add
an HTTP service without a requirement?

Bring independent results and compare tradeoffs without claiming the paired group's
implementation is incorrect merely because its folder structure differs.
