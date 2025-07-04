<?php

declare(strict_types=1);

namespace BaliNomad\SimpleConfig;

/**
 * A simple, dot-notation based configuration manager.
 *
 * @implements \IteratorAggregate<string|int, mixed>
 * @implements \ArrayAccess<string|int, mixed>
 */
class Config implements \ArrayAccess, \IteratorAggregate, \Countable
{
    public const MERGE_REPLACE = 1; // Replace original value (default)
    public const MERGE_KEEP = 2;    // Keep original value
    public const MERGE_APPEND = 3;  // Append new value, converting to array if necessary

    /**
     * The configuration array.
     *
     * @var array<string|int, mixed> $config
     */
    protected array $config = [];

    /**
     * Constructor.
     *
     * @param null|array<string|int, mixed> $config Configuration settings to start with.
     */
    public function __construct(?array $config = null)
    {
        if ($config !== null) {
            $this->config = $this->recursiveClean($config);
        }
    }

    /**
     * Retrieves a configuration value using dot notation.
     *
     * @param string     $key     The dot notation key to access the configuration value.
     * @param mixed|null $default The default value to return if the key is not found.
     *                            Defaults to null.
     *
     * @return mixed              The value associated with the key or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $config = $this->config;
        $segments = $this->parseKey($key);

        foreach ($segments as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Sets a configuration value using dot notation.
     * Setting a value to `null` will unset the key.
     *
     * @param string $key   Dot notation key
     * @param mixed  $value Config item value
     *
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        if ($value === null) {
            return $this->unset($key);
        }

        $config = &$this->config;
        $segments = $this->parseKey($key);

        foreach ($segments as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }
        $config = $value;

        return $this;
    }

    /**
     * Checks if a key exists using dot notation.
     *
     * @param string $key Dot notation key
     *
     * @return bool True if the key exists, false otherwise
     */
    public function has(string $key): bool
    {
        $config = $this->config;
        $segments = $this->parseKey($key);

        foreach ($segments as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return false;
            }
            $config = $config[$segment];
        }

        return true;
    }

    /**
     * Removes a key using dot notation and cleans up empty parent arrays.
     *
     * @param string $key Dot notation key
     *
     * @return self
     */
    public function unset(string $key): self
    {
        $segments = $this->parseKey($key);
        if (!empty($segments)) {
            $this->recursiveUnset($this->config, $segments);
        }
        return $this;
    }

    /**
     * Appends value(s) to an array at the specified key.
     *
     * @param string        $key   Dot notation key
     * @param mixed|mixed[] $value Value or values to append
     *
     * @return self
     */
    public function append(string $key, mixed $value): self
    {
        $original = $this->get($key, []);
        $newValue = array_merge(static::wrap($original), static::wrap($value));
        $this->set($key, $newValue);

        return $this;
    }

    /**
     * Subtracts value(s) from an array at the specified key.
     * If the resulting array is empty, the key is unset.
     *
     * @param string        $key   Dot notation key
     * @param mixed|mixed[] $value Value or values to remove
     *
     * @return self
     */
    public function subtract(string $key, mixed $value): self
    {
        $original = $this->get($key);
        if (!is_array($original)) {
            return $this;
        }

        $toRemove = static::wrap($value);
        $newValue = static::isAssoc($original)
            ? array_diff($original, $toRemove)
            : array_values(array_diff($original, $toRemove));

        if (empty($newValue)) {
            return $this->unset($key);
        }

        $this->set($key, $newValue);

        return $this;
    }

    /**
     * Merges another configuration array or Config object.
     *
     * @param null|self|array<string|int, mixed> $config Configuration array or class
     * @param int                                $method Merging strategy (REPLACE, KEEP, or APPEND)
     *
     * @return self
     */
    public function merge($config, int $method = self::MERGE_REPLACE): self
    {
        $configArray = ($config instanceof self) ? $config->toArray() : ($config ?? []);

        $this->config = ($method === self::MERGE_KEEP)
            ? $this->mergeArrays($configArray, $this->config)
            : $this->mergeArrays($this->config, $configArray, $method);

        return $this;
    }

    /**
     * Returns a new Config instance for a specific key.
     *
     * @param string $key Dot notation key for the configuration item
     *
     * @return self A new Config instance containing the split configuration
     */
    public function split(string $key): self
    {
        $value = $this->get($key, []);
        return new self(is_array($value) ? $value : [$value]);
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

    /**
     * Magic method for serializing the object.
     *
     * @return array<string|int, mixed> The configuration array
     */
    public function __serialize(): array
    {
        return $this->config;
    }

    /**
     * Magic method for restoring the configuration from a given serialized array.
     *
     * @param array<string|int, mixed> $data The serialized configuration data
     */
    public function __unserialize(array $data): void
    {
        $this->config = $data;
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
     * Sets the value at the specified offset.
     *
     * @param string|int $offset The offset at which to set the value
     * @param mixed      $value  The value to set
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string)$offset, $value);
    }

    /**
     * Removes the value associated with the given offset.
     *
     * @param string|int $offset The offset of the value to remove
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->unset((string)$offset);
    }

    /**
     * Counts all leaf configuration values.
     * A leaf is any non-associative-array value.
     *
     * @return int The number of leaf items in the config
     */
    public function count(): int
    {
        return $this->recursiveCount($this->config);
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
    public static function wrap(mixed $value): array
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
    public static function isAssoc(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Recursively removes null values and empty arrays from the configuration.
     *
     * @param array<string|int, mixed> $data The data array to clean
     *
     * @return array<string|int, mixed> The cleaned array with non-null
     *                                  and non-empty elements
     */
    private function recursiveClean(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->recursiveClean($value);
                if (!empty($value)) {
                    $result[$key] = $value;
                }
            } elseif ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Recursively unsets a key and cleans up empty parent arrays.
     *
     * @param array<string|int, mixed> &$config The configuration array
     * @param string[] $segments                The key segments
     */
    private function recursiveUnset(array &$config, array $segments): void
    {
        $segment = array_shift($segments);

        if (!isset($segment) || !is_array($config) || !array_key_exists($segment, $config)) {
            return;
        }

        if (!empty($segments)) {
            $this->recursiveUnset($config[$segment], $segments);
        } else {
            unset($config[$segment]);
            return;
        }

        if (isset($config[$segment]) && is_array($config[$segment]) && empty($config[$segment])) {
            unset($config[$segment]);
        }
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
    private function mergeArrays(array $base, array $replacement, int $method = self::MERGE_REPLACE): array
    {
        foreach ($replacement as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }

            $baseValue = $base[$key];
            if (
                is_array($value) && is_array($baseValue) &&
                static::isAssoc($value) && static::isAssoc($baseValue)
            ) {
                $base[$key] = $this->mergeArrays($baseValue, $value, $method);
                continue;
            }

            if ($method !== self::MERGE_APPEND || (!is_array($baseValue) && !is_array($value))) {
                $base[$key] = $value;
                continue;
            }

            $base[$key] = array_merge(static::wrap($baseValue), static::wrap($value));
        }

        return $base;
    }

    /**
     * Parses a dot-notation key into segments.
     *
     * @param string $key The dot-notation key
     *
     * @return string[] The key segments
     */
    private function parseKey(string $key): array
    {
        return array_filter(explode('.', $key), fn($s) => $s !== '');
    }

    /**
     * Recursively counts leaf nodes in a configuration array.
     *
     * @param array<string|int, mixed> $data The data array to count
     *
     * @return int The number of leaf nodes
     */
    private function recursiveCount(array $data): int
    {
        $count = 0;
        foreach ($data as $value) {
            // Recurse only for associative arrays. Lists are counted as one leaf.
            if (is_array($value) && static::isAssoc($value)) {
                $count += $this->recursiveCount($value);
            } else {
                $count++;
            }
        }
        return $count;
    }
}
