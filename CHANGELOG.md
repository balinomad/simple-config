# Changelog

---

All notable changes to `balinomad/simple-config` will be documented in this file.

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
