<?php

namespace BaliNomad\SimpleConfig;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use RuntimeException;
use Serializable;
use Traversable;

/**
 * Config class.
 *
 * @implements IteratorAggregate<string|int>
 * @implements ArrayAccess<string|int, mixed>
 */
class Config implements ArrayAccess, IteratorAggregate, Serializable, Countable
{
    // Replace the original value (default)
    const MERGE_REPLACE = 1;

    // Keep the original value
    const MERGE_KEEP = 2;

    // Append the new value and convert to array if necessary
    const MERGE_APPEND = 3;

    /**
     * Configuration settings.
     *
     * @var array<string|int, mixed>
     */
    protected $config = [];

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

        /**
         * @var string $k
         */
        foreach (explode('.', $key) as $k) {
            /**
             * @var array $config<string, mixed>
             */
            $config = &$config[$k];
        }

        $config = $value;

        return $this;
    }

    /**
     * Completely removes a key.
     *
     * @param  string $key Dot notation key
     * @return self
     */
    public function unset(string $key): self
    {
        $config = &$this->config;

        foreach (explode('.', $key) as $k) {
            if (!is_array($config) || !isset($config[$k])) {
                return $this;
            }
            $config = &$config[$k];
        }

        $config = null;

        return $this;
    }

    /**
     * Retrieves a key value.
     *
     * @param  string     $key     Dot notation key
     * @param  mixed|null $default Default value
     * @return mixed      Value or default
     */
    public function get(string $key, $default = null)
    {
        $config = $this->config;

        foreach (explode('.', $key) as $k) {
            if (!is_array($config)) {
                throw new RuntimeException('Config does not have key of `' . $key . '` set.');
            }
            if (!isset($config[$k])) {
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

        /**
         * @var string $k
         */
        foreach (explode('.', $key) as $k) {
            if (!is_array($config) || !isset($config[$k])) {
                return false;
            }
            $config = $config[$k];
        }

        return true;
    }

    /**
     * Appends value(s) to an array.
     *
     * @param  string        $key   Dot notation key
     * @param  mixed|mixed[] $value Value or values to append
     * @return self
     */
    public function append(string $key, $value): self
    {
        $config = &$this->config;

        foreach (explode('.', $key) as $k) {
            /**
             * @var array $config<string|int, mixed>
             */
            $config = &$config[$k];
        }

        $config = array_merge(static::wrap($config), static::wrap($value));

        return $this;
    }

    /**
     * Substract value(s) from an array.
     *
     * Non-associative arrays will be re-indexed.
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
                throw new RuntimeException('Config does not have key of `' . $key . '` set.');
            }
            if (!isset($config[$k])) {
                return $this;
            }
            $config = &$config[$k];
        }

        $value = static::wrap($value);

        if (is_array($config)) {
            $config = static::isAssoc($config)
                ? array_diff($config, $value)
                : array_values(array_diff($config, $value));
        } elseif (in_array($config, $value)) {
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
        $config = ($config instanceof self)
            ? $config->toArray()
            : $config ?? [];

        $method = $method ?? self::MERGE_REPLACE;

        if (self::MERGE_KEEP === $method) {
            $base = $config;
            $replacement = $this->config;
        } else {
            $base = $this->config;
            $replacement = $config;
        }

        $replaced = $this->replace($base, $replacement, $method) ?? [];
        if (!is_array($replaced)) {
            $replaced = [$replaced];
        }
        $this->config = $replaced;

        return $this;
    }

    /**
     * Recursive value replacement.
     *
     * @param  null|array<string, mixed>|mixed $base
     * @param  null|array<string, mixed>|mixed $replacement
     * @param  int                             $method
     * @return null|array<string, mixed>|mixed
     */
    protected function replace($base, $replacement, int $method)
    {
        if (empty($replacement)) {
            if (!is_array($base) || !static::isAssoc($base)) {
                return static::wrap($base);
            }

            return $base;
        }

        if (!is_array($base) || !static::isAssoc($base) || !is_array($replacement) || !static::isAssoc($replacement)) {
            if (self::MERGE_APPEND === $method) {
                return array_unique(array_merge(static::wrap($base), static::wrap($replacement)));
            }

            return $replacement;
        }

        foreach (static::commonKeys($base, $replacement) as $key) {
            $base[$key] = $this->replace($base[$key], $replacement[$key], $method);
        }

        $merge = $base + $replacement;
        if (!is_array($merge) || !static::isAssoc($merge)) {
            return static::wrap($merge);
        }

        /**
         * @var array<string, mixed> $merge
         */
        return $merge;
    }

    /**
     * Splits a sub-array of configuration options into a new config.
     *
     * @param  string $key Dot notation key
     * @return Config
     */
    public function split(string $key): self
    {
        $value = $this->get($key) ?? [];
        if (!is_array($value)) {
            $value = [$value];
        }

        return new self($value);
    }

    /**
     * Returns the entire configuration as an array.
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * @param  string $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return $this->has((string) $offset);
    }

    /**
     * @param  string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param  string $offset
     * @param  mixed  $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->set((string) $offset, $value);
    }

    /**
     * @param  string $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        $this->unset((string) $offset);
    }

    /**
     * @return Traversable <string|int, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->config);
    }

    /**
     * Generates a storable representation of the configuration.
     *
     * @return string|null
     */
    public function serialize(): ?string
    {
        return serialize($this->config);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function __serialize(): array
    {
        return $this->config;
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
     * @param  array<string, bool|int|string> $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->config = $data;
    }

    /**
     * Counts the config items.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->config, COUNT_RECURSIVE);
    }

    /**
     * If the given value is not an array, wraps it in one.
     *
     * @param  mixed   $value
     * @return mixed[]
     */
    public static function wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Tests if the array is associative.
     *
     * Note that this function will return false if an array is empty. Meaning
     * empty arrays will be treated as if they are not associative arrays.
     *
     * @param  mixed[] $array
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        return 0 < count($array) && count(array_filter(array_keys($array), 'is_string')) == count($array);
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
}
