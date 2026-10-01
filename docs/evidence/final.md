# Final evidence

PHP 8.4.21; PHPUnit 12.5.37; PHPStan 2.2.16; Composer 2.10.2.

| Check | Command | Result |
| --- | --- | --- |
| Behavior | `vendor/bin/phpunit --colors=never` | 16 tests, 87 assertions, exit 0 |
| Quality | `vendor/bin/phpstan analyse --debug --no-progress` | Maximum level, no errors, exit 0 |
| Metadata | `composer validate --strict` | Valid, exit 0 |
| Syntax | `php -l` on production/examples/tests/entries | 25 files, zero failures |
| Clean checkout | `composer install` then PHPUnit in a temporary Git archive | Install/test exit 0; 16 tests, 87 assertions |
| Dependencies | `composer audit --locked --format=json` | See final-audit.json |

Baseline PHPStan scope was the original script; final scope is all production code
and the example. Test fixtures are excluded from quality analysis deliberately.
Before/after PNGs show the representative 00001 output; use `shasum -a 256` on both.
The baseline characterization only runs single boundary images and the final test
checks all 10,000 payload strings, not a complete 10,000-image batch. No benchmark,
independent QR decoding, or claim of exhaustive security testing is made.

For reproduction of storage RED→GREEN:

```sh
git show 752ec31:src/Infrastructure/FilesystemImageStore.php
# This version writes directly; the safety test demonstrates target overwrite.
# Current version writes a complete temporary file and renames it.
vendor/bin/phpunit --filter FilesystemSafetyTest
```

Run the full verification commands from README after checking out this branch.
Fetch the final revision with `git rev-parse HEAD` and its authorship with
`git log --format='%h %an <%ae> %s' 65a17a2..HEAD`.
