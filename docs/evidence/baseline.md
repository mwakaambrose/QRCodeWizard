# Baseline evidence

Production baseline: 65a17a2. PHP 8.4.21, PHPUnit 12.5.37, PHPStan 2.2.16,
Composer 2.10.2. No tests existed before this work.

- `vendor/bin/phpunit`: exit 0; 1 test, 10 assertions.
- The verbatim legacy fixture is run in temporary directories. Only the autoload
  location and loop bounds are adapted to execute one image at 1 and 10000.
  This verifies boundary filenames and real labeled PNGs; it does not prove a full
  10,000-file run or independently decode the QR payload.
- `vendor/bin/phpstan analyse generate_qrcodes.php --level=max --no-progress --debug`:
  exit 1; one argument.type finding: an integer is passed to str_pad's string parameter.
  PHP currently coerces it in the original non-strict file. This is a valid contract
  weakness; the replacement explicitly converts the number to a string.
- `composer audit --locked --format=json`: exit 0; see baseline-audit.json.
  This audit includes newly installed development dependencies; it is not a historic
  audit captured in 2025. Production dependency versions were not changed.

Raw results are stored alongside this document. --debug runs PHPStan without
parallel worker sockets, which the sandbox disallows.
