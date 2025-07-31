<?php

declare(strict_types=1);

namespace BaliNomad\SimpleConfig;

/**
 * A simple, immutable, dot-notation based configuration manager.
 *
 * @implements \IteratorAggregate<string|int, mixed>
 * @implements \ArrayAccess<string|int, mixed>
 */
class Config implements \ArrayAccess, \IteratorAggregate, \Countable
{
    public const MERGE_REPLACE = 1; // Replace original value (default)
    public const MERGE_KEEP = 2;    // Keep original value
    public const MERGE_APPEND = 3;  // Append new value, converting to array if necessary

    public const CLEAN_NONE = 0;
    public const CLEAN_NULLS = 1;
    public const CLEAN_EMPTY_ARRAYS = 2;
    public const CLEAN_ALL = self::CLEAN_NULLS | self::CLEAN_EMPTY_ARRAYS;

    /**
     * The configuration array.
     *
     * @var array<string|int, mixed>
     */
    private readonly array $config;

    /**
     * The cleaning policy for this instance.
     */
    private readonly int $cleaningFlags;

    /**
     * @param null|array<string|int, mixed> $config        Configuration settings to start with.
     * @param int                           $cleaningFlags A bitmask of cleaning options.
     */
    public function __construct(?array $config = null, int $cleaningFlags = self::CLEAN_NULLS)
    {
        $this->cleaningFlags = $cleaningFlags;
        $this->config = self::clean($config ?? [], $this->cleaningFlags);
    }

    /**
     * Retrieves a configuration value using dot notation.
     *
     * @param string $key     The dot notation key.
     * @param mixed  $default The default value to return if the key is not found.
     *
     * @return mixed The value associated with the key or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $config = $this->config;
        $segments = self::parseKey($key);

        foreach ($segments as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Checks if a key exists.
     *
     * @param string $key Dot notation key
     *
     * @return bool True if the key exists, false otherwise
     */
    public function has(string $key): bool
    {
        // Use a unique marker to distinguish between a null value and a non-existent key
        $marker = new \stdClass();
        return $this->get($key, $marker) !== $marker;
    }

    /**
     * Returns a new Config instance with a value set or updated.
     *
     * @param string $key   Dot notation key
     * @param mixed  $value The value to set
     *
     * @return self A new Config instance containing the updated configuration
     */
    public function with(string $key, mixed $value): self
    {
        if ($value === null && ($this->cleaningFlags & self::CLEAN_NULLS)) {
            return $this->without($key);
        }
        $newConfig = self::setValue($this->config, self::parseKey($key), $value);
        return new self($newConfig, $this->cleaningFlags);
    }

    /**
     * Returns a new Config instance with a key removed.
     *
     * @param string $key Dot notation key
     *
     * @return self A new Config instance containing the updated configuration
     */
    public function without(string $key): self
    {
        $newConfig = self::unsetValue($this->config, self::parseKey($key));
        return new self($newConfig, $this->cleaningFlags);
    }

    /**
     * Appends value(s) to an array at the specified key.
     *
     * @param string $key   Dot notation key
     * @param mixed  $value Value or values to append
     *
     * @return self
     */
    public function append(string $key, mixed $value): self
    {
        $original = $this->get($key, []);
        $newValue = array_merge(self::wrap($original), self::wrap($value));
        return $this->with($key, $newValue);
    }

    /**
     * Subtracts a value from an array, returning a new Config instance.
     *
     * @param string $key   Dot notation key
     * @param mixed  $value Value to remove
     *
     * @return self
     */
    public function subtract(string $key, mixed $value): self
    {
        $original = $this->get($key);
        if (!is_array($original)) {
            return $this;
        }

        $toRemove = self::wrap($value);
        $newValue = self::isAssoc($original)
            ? array_diff($original, $toRemove)
            : array_values(array_diff($original, $toRemove));

        return $this->with($key, $newValue);
    }

    /**
     * Merges another configuration, returning a new Config instance.
     *
     * @param null|self|array<string, mixed> $config The configuration to merge
     * @param int                            $method Merging strategy
     *
     * @return self A new Config instance containing the merged configuration
     */
    public function merge(mixed $config, int $method = self::MERGE_REPLACE): self
    {
        $otherArray = ($config instanceof self)
            ? $config->toArray()
            : ($config ?? []);
        $merged = self::mergeArrays($this->config, $otherArray, $method);
        return new self($merged, $this->cleaningFlags);
    }

    /**
     * Returns a new Config instance for a specific key.
     *
     * @param string $key Dot notation key
     *
     * @return self A new Config instance containing the split configuration
     */
    public function split(string $key): self
    {
        $value = $this->get($key, []);
        $data = is_array($value) ? $value : [$value];
        return new self($data, $this->cleaningFlags);
    }

    /**
     * Returns the entire configuration as an array.
     *
     * @return array<string|int, mixed>
     */
    public function toArray(): array
    {
        return $this->config;
    }

    // @codeCoverageIgnoreStart

    /**
     * @deprecated 1.0.0 Use with() instead for an immutable operation.
     *
     * Sets a configuration value using dot notation.
     *
     * @param string $key   Dot notation key
     * @param mixed  $value Config item value
     *
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        return $this->with($key, $value);
    }

    /**
     * @deprecated 1.0.0 Use without() instead for an immutable operation.
     *
     * Removes a key using dot notation and cleans up empty parent arrays.
     *
     * @param string $key Dot notation key
     *
     * @return self
     */
    public function unset(string $key): self
    {
        return $this->without($key);
    }

    // @codeCoverageIgnoreEnd

    /**
     * Magic method for serializing the object.
     *
     * @return array{config: array<string|int, mixed>, cleaningFlags: int} The configuration array
     */
    public function __serialize(): array
    {
        return ['config' => $this->config, 'cleaningFlags' => $this->cleaningFlags];
    }

    /**
     * Magic method for restoring the configuration from a given serialized array.
     *
     * @param array{config: array<string|int, mixed>, cleaningFlags?: int} $data The serialized configuration data
     */
    public function __unserialize(array $data): void
    {
        $this->config = $data['config'];
        $this->cleaningFlags = $data['cleaningFlags'] ?? self::CLEAN_NULLS;
    }

    /**
     * Checks if the specified offset exists.
     *
     * @param string|int $offset The offset to check
     *
     * @return bool True if the offset exists, false otherwise
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    /**
     * Retrieves the value at the specified offset.
     *
     * @param string|int $offset The offset of the value to retrieve
     *
     * @return mixed The value associated with the given offset
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string)$offset);
    }

    /**
     * Throws an exception when trying to set a value.
     *
     * @param mixed $offset Unused parameter
     * @param mixed $value  Unused parameter
     *
     * @throws \LogicException Cannot modify configuration via array access
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException(sprintf(
            'Cannot modify %s via array access. Use with() instead.',
            self::class
        ));
    }

    /**
     * Throws an exception when trying to unset a value.
     *
     * @param mixed $offset Unused parameter
     *
     * @throws \LogicException Cannot modify configuration via array access
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException(sprintf(
            'Cannot modify %s via array access. Use without() instead.',
            self::class
        ));
    }

    /**
     * Counts all leaf configuration values.
     *
     * Note that non-associative arrays (lists) are counted as a single leaf.
     *
     * @return int The number of leaf items in the config
     */
    public function count(): int
    {
        return self::countLeaves($this->config);
    }

    /**
     * Retrieves an external iterator.
     *
     * @return \Traversable<string|int, mixed> An iterator implementing the Traversable interface,
     *                                         allowing iteration over the configuration items
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->config);
    }

    /**
     * Wraps a value in an array unless it is already an array.
     *
     * @param mixed $value The value to wrap
     *
     * @return array<string|int, mixed> The wrapped array
     */
    private static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }

    /**
     * Heuristically determines if an array is associative.
     *
     * Note that this function will return false if an array is empty. Meaning
     * empty arrays will be treated as if they are not associative arrays.
     *
     * @param array<int|string, mixed> $array The array to check
     *
     * @return bool True if the array is associative, false otherwise
     */
    private static function isAssoc(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }


    /**
     * Recursively sets a value in a nested array.
     *
     * @param array<string|int, mixed> $config The array to set the value in
     * @param array<int, string>       $segments The segments of the key to set
     * @param mixed                    $value   The value to set
     *
     * @return array<string|int, mixed> The updated array
     */
    private static function setValue(array $config, array $segments, mixed $value): array
    {
        $key = array_shift($segments);
        if ($key === null) {
            return $config;
        }

        if (!empty($segments)) {
            $subConfig = $config[$key] ?? [];
            $config[$key] = self::setValue(is_array($subConfig) ? $subConfig : [], $segments, $value);
        } else {
            $config[$key] = $value;
        }

        return $config;
    }

    /**
     * Recursively unsets a value from a nested array.
     *
     * @param array<string|int, mixed> $config The array to unset the value from
     * @param array<int, string>       $segments The segments of the key to unset
     *
     * @return array<string|int, mixed> The updated array
     */
    private static function unsetValue(array $config, array $segments): array
    {
        if (empty($segments) || empty($config)) {
            return $config;
        }

        $key = array_shift($segments);

        if (!array_key_exists($key, $config)) {
            return $config;
        }

        if (empty($segments)) {
            unset($config[$key]);
        } elseif (is_array($config[$key])) {
            $config[$key] = self::unsetValue($config[$key], $segments);
        }

        return $config;
    }

    /**
     * Recursively removes values from a given array based on cleaning flags.
     *
     * @param array<string|int, mixed> $data The data array to clean
     * @param int                      $cleaningFlags A bitmask of cleaning options
     *
     * @return array<string|int, mixed> The cleaned array
     */
    private static function clean(array $data, int $cleaningFlags): array
    {
        $result = [];
        $cleanNulls = ($cleaningFlags & self::CLEAN_NULLS) !== 0;
        $cleanEmpty = ($cleaningFlags & self::CLEAN_EMPTY_ARRAYS) !== 0;

        foreach ($data as $key => $value) {
            if ($cleanNulls && $value === null) {
                continue;
            }

            if (is_array($value)) {
                $value = self::clean($value, $cleaningFlags);
                if ($cleanEmpty && empty($value)) {
                    continue;
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Recursively merges two arrays.
     * Differentiates between associative arrays (which are merged recursively)
     * and list-style arrays (which are replaced or appended as a whole).
     *
     * @param array<string|int, mixed> $base        The base array
     * @param array<string|int, mixed> $replacement The replacement array
     * @param int                      $method      Merging strategy (REPLACE, KEEP, or APPEND)
     *
     * @return array<string|int, mixed> The merged array
     */
    private static function mergeArrays(array $base, array $replacement, int $method): array
    {
        foreach ($replacement as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }

            if ($method === self::MERGE_KEEP) {
                continue;
            }

            $baseValue = $base[$key];

            if (self::bothAreAssocArrays($baseValue, $value)) {
                /**
                 * @var array<string, mixed> $baseValue
                 * @var array<string, mixed> $value
                 */
                $base[$key] = self::mergeArrays($baseValue, $value, $method);
            } elseif ($method === self::MERGE_APPEND && self::bothAreLists($baseValue, $value)) {
                /**
                 * @var array<int, mixed> $baseValue
                 * @var mixed             $value
                 */
                $base[$key] = array_merge($baseValue, self::wrap($value));
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Parses a dot-notation key into segments.
     *
     * @param string $key The dot-notation key
     *
     * @return array<int, string> The key segments
     */
    private static function parseKey(string $key): array
    {
        return array_filter(explode('.', $key), fn($s) => $s !== '');
    }

    /**
     * Recursively counts leaf nodes in an array.
     *
     * @param array<string|int, mixed> $data The data array to count
     *
     * @return int The number of leaf nodes
     */
    private static function countLeaves(array $data): int
    {
        $count = 0;
        foreach ($data as $value) {
            // Recurse only for associative arrays. Lists are counted as one leaf.
            if (is_array($value) && self::isAssoc($value)) {
                $count += self::countLeaves($value);
            } else {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Checks if both $a and $b are associative arrays.
     *
     * @param mixed $a The first value to check
     * @param mixed $b The second value to check
     *
     * @return bool True if both $a and $b are associative arrays, false otherwise
     */
    private static function bothAreAssocArrays(mixed $a, mixed $b): bool
    {
        return is_array($a) && self::isAssoc($a) && is_array($b) && self::isAssoc($b);
    }

    /**
     * Checks if both $a and $b are lists (non-associative arrays).
     *
     * $a must be a list, and $b must either not be an array, or be a list.
     *
     * @param mixed $a The first value to check
     * @param mixed $b The second value to check
     *
     * @return bool True if both $a and $b are lists, false otherwise
     */
    private static function bothAreLists(mixed $a, mixed $b): bool
    {
        return is_array($a) && !self::isAssoc($a) &&
            (!is_array($b) || !self::isAssoc($b));
    }
}
