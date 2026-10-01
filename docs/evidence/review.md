# Final independent review

A fresh reviewer inspected baseline 65a17a2 through c533094 without editing source.
It confirmed the suite, maximum PHPStan analysis, Composer metadata, range handling,
CLI behavior, and required commit authorship/count. It requested two fixes:

1. Atomic replacement initially changed permissions to 0600. A regression test
   reproduced this (expected 0644 under umask 0022; actual 0600). The implementation
   now respects umask for new files and preserves existing regular-file permission
   bits. The test also verifies replacement retains an explicitly set 0640 mode.
2. SVG's SimpleXML runtime dependency was undeclared. Composer now declares
   ext-simplexml; `composer check-platform-reqs --no-dev` confirms it and PHP >=8.4
   alongside GD and transitive requirements.

The reviewer also identified Endroid's unlabeled SVG output. It is documented;
PNG labels/default behavior remain unchanged. No additional SVG feature was added.
Final suite after fixes: 16 tests, 87 assertions; maximum PHPStan: zero errors.

Review did not claim to establish full-batch performance, independent decoding,
crash durability, hostile-directory race safety, or cross-platform atomicity.
These remain explicit limits in the coursework explanation. Fix verification is
through the targeted regression and full suite, not a second independent review.
