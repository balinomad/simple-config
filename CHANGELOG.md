# Changelog

---

All notable changes to `balinomad/simple-config` will be documented in this file.

## v1.0.0 - 2025-07-31

### Major Changes & Backward Compatibility Breaks

-   **Immutability**: The `Config` class is now immutable. The methods `set()`, `unset()`, `append()`, `subtract()`, and `merge()` no longer modify the existing object but return a new `Config` instance with the changes. This is a significant breaking change.
-   **ArrayAccess Modification Disabled**: Setting or unsetting configuration values via array access (e.g., `$config['foo'] = 'bar';` or `unset($config['foo']);`) is no longer allowed and will throw a `LogicException`.
-   **PHP 8.0 support dropped**: PHP 8.1+ is now required for this library.

### Added

-   **Immutable Setters**: Introduced new immutable methods `with()` and `without()` as the primary way to create modified configurations.
-   **Configurable Cleaning Policies**: The constructor now accepts cleaning flags (`CLEAN_NULLS`, `CLEAN_EMPTY_ARRAYS`, `CLEAN_ALL`, `CLEAN_NONE`) to control the automatic removal of `null` values and empty arrays.
-   The class is now `readonly`, enforcing immutability at the property level.

### Changed

-   The serialization methods (`__serialize`, `__unserialize`) have been updated to include the new cleaning policy flags.
-   Internal helper methods have been refactored to be `private static` for better encapsulation and performance.

### Improved

-   The `has()` method now accurately distinguishes between a key with a `null` value and a non-existent key.

### Deprecated

-   The mutable methods `set()` and `unset()` are now deprecated. Use the new immutable `with()` and `without()` methods instead.


## v0.3.0 - 2025-07-04

-   Minimum PHP version requirement changed to v8.0.
-   `commonKeys` method removed.
-   `count` method refactored to calculate the number of scalar and non-associative array items.
-   Keys with null values and empty arrays are automatically removed from the configuration.
-   Code structure, performance, and documentation has been improved.
-   Static analysis has been improved.
-   Additional unit tests have been added and the code coverage has been increased to 100%.

## v0.2.2 - 2025-07-03

-   Namespace replacement of `Navindex\SimpleConfig` added to `composer.json`.

## v0.2.1 - 2025-07-03

-   Namespace change from `Navindex\SimpleConfig` to `BaliNomad\SimpleConfig`.

## v0.2.0 - 2021-09-27

### Fixed

-   `merge` with `MERGE_KEEP` setting did not work properly for empty arrays.
-   Unused composer dependencies have been removed.

### Changed

-   `merge` now accepts `\BaliNomad\SimpleConfig\Config` instance or`array` attribute. Previously it was set to `array` only.
-   `count` method now recursively counts all configuration items, not just the top level.

### Added

-   List of public class methods added to the Readme file.

## v0.1.0 - 2021-09-26

Initial release.
