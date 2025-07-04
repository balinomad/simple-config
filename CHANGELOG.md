# Changelog

---

All notable changes to `balinomad/simple-config` will be documented in this file.

## 0.3.0 - 2025-07-04

-   Minimum PHP version requirement changed to v8.0.
-   `commonKeys` method removed.
-   `count` method refactored to calculate the number of scalar and non-associative array items.
-   Keys with null values and empty arrays are automatically removed from the configuration.
-   Code structure, performance, and documentation has been improved.
-   Static analysis has been improved.
-   Additional unit tests have been added and the code coverage has been increased to 100%.

## 0.2.2 - 2025-07-03

-   Namespace replacement of `Navindex\SimpleConfig` added to `composer.json`.

## 0.2.1 - 2025-07-03

-   Namespace change from `Navindex\SimpleConfig` to `BaliNomad\SimpleConfig`.

## 0.2.0 - 2021-09-27

### Fixed

-   `merge` with `MERGE_KEEP` setting did not work properly for empty arrays.
-   Unused composer dependencies have been removed.

### Changed

-   `merge` now accepts `\BaliNomad\SimpleConfig\Config` instance or`array` attribute. Previously it was set to `array` only.
-   `count` method now recursively counts all configuration items, not just the top level.

### Added

-   List of public class methods added to the Readme file.

## 0.1.0 - 2021-09-26

Initial release.
