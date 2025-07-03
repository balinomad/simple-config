<?php

namespace BaliNomad\SimpleConfig;

/**
 * Config class.
 *
 * @implements \IteratorAggregate<string|int>
 * @implements \ArrayAccess<string|int, mixed>
 */
class Config implements \ArrayAccess, \IteratorAggregate, \Serializable, \Countable
{
    public const MERGE_REPLACE = 1; // Replace the original value (default)
    public const MERGE_KEEP = 2;    // Keep the original value
    public const MERGE_APPEND = 3;  // Append the new value and convert to array if necessary

    /**
     * Configuration settings.
     *
     * @var array<string|int, mixed>
     */
    protected array $config = [];

    /**
     * Constructor.
     *
     * @param null|array<string|int, mixed> $config Configuration array
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [];
    }

    /**
     * Saves a key value.
     *
     * @param  string $key   Dot notation key
     * @param  mixed  $value Config item value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $config = &$this->config;
        foreach (explode('.', $key) as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        $config = $value;
        return $this;
    }

    /**
     * Unsets a key from the configuration.
     *
     * @param  string $key Dot notation key
     * @return self
     */
    public function unset(string $key): self
    {
        $config = &$this->config;
        $segments = explode('.', $key);
        $last = array_pop($segments);

        foreach ($segments as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                return $this;
            }
            $config = &$config[$k];
        }

        unset($config[$last]);
        return $this;
    }

    /**
     * Retrieves the value associated with the specified dot notation key.
     *
     * Traverses the configuration array using the dot notation key to
     * locate the desired value. If the key does not exist, returns the
     * provided default value.
     *
     * @param string     $key     The dot notation key.
     * @param mixed|null $default The default value to return if the key is not found.
     * @return mixed              The value associated with the key or the default value.
     * @throws \RuntimeException  If the configuration does not contain the specified key.
     */
    public function get(string $key, $default = null)
    {
        $config = $this->config;
        foreach (explode('.', $key) as $k) {
            if (!is_array($config)) {
                throw new \RuntimeException("Config does not have key of `$key` set.");
            }
            if (!array_key_exists($k, $config)) {
                return $default;
            }
            $config = $config[$k];
        }
        return $config;
    }

    /**
     * Checks if a key exists and not null.
     *
     * @param  string $key Dot notation key
     * @return bool   True if the key exists, false otherwise
     */
    public function has(string $key): bool
    {
        $config = $this->config;
        foreach (explode('.', $key) as $k) {
            if (!is_array($config) || !array_key_exists($k, $config)) {
                return false;
            }
            $config = $config[$k];
        }
        return true;
    }

    /**
     * Appends value(s) to an array.
     *
     * Non-associative arrays will be re-indexed.
     *
     * @param  string        $key   Dot notation key
     * @param  mixed|mixed[] $value Value or values to append
     * @return self
     */
    public function append(string $key, $value): self
    {
        $config = &$this->config;
        foreach (explode('.', $key) as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        $config = array_merge(static::wrap($config), static::wrap($value));
        return $this;
    }

    /**
     * Subtracts value(s) from an array at the specified dot notation key.
     *
     * Non-associative arrays will be re-indexed after subtraction.
     * If the specified key does not exist, the method will return without
     * making any changes. Throws a RuntimeException if the configuration
     * does not contain the specified key.
     *
     * @param  string        $key   Dot notation key
     * @param  mixed|mixed[] $value Value or values to remove
     * @return self
     */
    public function subtract(string $key, $value): self
    {
        $config = &$this->config;
        foreach (explode('.', $key) as $k) {
            if (!is_array($config)) {
                throw new \RuntimeException("Config does not have key of `$key` set.");
            }
            if (!array_key_exists($k, $config)) {
                return $this;
            }
            $config = &$config[$k];
        }

        $value = static::wrap($value);
        if (is_array($config)) {
            $config = static::isAssoc($config)
                ? array_diff($config, $value)
                : array_values(array_diff($config, $value));
        } elseif (in_array($config, $value, true)) {
            $config = [];
        }

        return $this;
    }

    /**
     * Merges another config into this one.
     *
     * @param  null|Config|array<string, mixed> $config Configuration array or class
     * @param  null|int                         $method Merging method
     * @return self
     */
    public function merge($config, ?int $method = null): self
    {
        $config = ($config instanceof self) ? $config->toArray() : ($config ?? []);
        $method = $method ?? self::MERGE_REPLACE;

        if ($method === self::MERGE_KEEP) {
            $base = $config;
            $replacement = $this->config;
        } else {
            $base = $this->config;
            $replacement = $config;
        }

        $this->config = $this->replace($base, $replacement, $method) ?? [];

        return $this;
    }

    /**
     * Recursive value replacement.
     *
     * @param  null|array<string, mixed>|mixed $base        The base array
     * @param  null|array<string, mixed>|mixed $replacement The replacement array
     * @param  int                             $method      One of the MERGE_* constants
     * @return null|array<string, mixed>                    The merged array
     */
    protected function replace($base, $replacement, int $method)
    {
        if (empty($replacement)) {
            return static::wrap($base);
        }

        if (
            !is_array($base) || !is_array($replacement)
            || !static::isAssoc($base) || !static::isAssoc($replacement)
        ) {
            return $method === self::MERGE_APPEND
                ? array_unique(array_merge(static::wrap($base), static::wrap($replacement)))
                : static::wrap($replacement);
        }

        foreach (static::commonKeys($base, $replacement) as $key) {
            $base[$key] = $this->replace($base[$key], $replacement[$key], $method);
        }

        return $base + $replacement;
    }

    /**
     * Splits the configuration array at the specified dot notation key.
     *
     * This method retrieves the value associated with the given key and
     * initializes a new BaseConfig instance with that value. If the value
     * is not an array, it is wrapped into an array before creating the
     * new instance.
     *
     * @param  string $key Dot notation key for the configuration item.
     * @return self        A new BaseConfig instance containing the split configuration.
     */
    public function split(string $key): self
    {
        $value = $this->get($key) ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }

        return new Config($value);
    }

    /**
     * Returns the entire configuration as an array.
     *
     * @return array The entire configuration
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Generates a storable representation of the configuration.
     *
     * @return string|null The serialized configuration
     */
    public function serialize(): ?string
    {
        return serialize($this->config);
    }

    /**
     * Sets the configuration from a stored representation.
     *
     * @param  string $data
     * @return void
     */
    public function unserialize($data): void
    {
        $config = unserialize($data, ['allowed_classes' => false]);
        $this->config = is_array($config) ? $config : [];
    }

    /**
     * Magic method for serializing the object.
     *
     * @return array The configuration array
     */
    public function __serialize(): array
    {
        return $this->config;
    }

    /**
     * Restores the configuration from a given serialized array.
     *
     * This method takes an array representation of the configuration
     * and assigns it to the internal configuration property.
     *
     * @param array<string, mixed> $data The serialized configuration data.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->config = $data;
    }

    /**
     * Wraps a value in an array if it is not already an array.
     *
     * If the value is null, an empty array is returned.
     *
     * @param  mixed $value
     * @return array
     */
    public static function wrap($value): array
    {
        return is_array($value) ? $value : (is_null($value) ? [] : [$value]);
    }

    /**
     * Tests if the array is associative.
     *
     * An associative array is one where the keys are not in a continuous
     * sequence starting from 0. This is a heuristic and may not work in
     * all cases, e.g. if the array is created with a non-sequential key
     * sequence, but is still indexed.
     *
     * Note that this function will return false if an array is empty. Meaning
     * empty arrays will be treated as if they are not associative arrays.
     *
     * @param  mixed[] $array
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        return $array !== [] && array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Returns the keys present in all arrays.
     *
     * @param  mixed[]  $array1
     * @param  mixed[]  $array2
     * @param  mixed[]  ...$_
     * @return string[]
     */
    public static function commonKeys(array $array1, array $array2, array ...$_): array
    {
        return array_keys(array_intersect_key($array1, $array2, ...$_));
    }

    /**
     * Checks if the specified offset exists.
     *
     * @param string|int $offset The offset to check.
     *
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    /**
     * Retrieves the value at the specified offset.
     *
     * @param string|int $offset The offset of the value to retrieve.
     *
     * @return mixed The value associated with the given offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    /**
     * Sets the value at the specified offset.
     *
     * @param string|int $offset The offset at which to set the value.
     * @param mixed      $value  The value to set.
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string)$offset, $value);
    }

    /**
     * Removes the value associated with the given offset.
     *
     * @param string|int $offset The offset of the value to remove.
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->unset((string)$offset);
    }

    /**
     * Counts the number of items in the config.
     *
     * @return int The number of items in the config.
     */
    public function count(): int
    {
        return count($this->config, COUNT_RECURSIVE);
    }

    /**
     * Retrieves an external iterator.
     *
     * @return \Traversable An iterator implementing the Traversable interface,
     *                      allowing iteration over the configuration items.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->config);
    }
}
