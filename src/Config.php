<?php

namespace BaliNomad\SimpleConfig;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Serializable;
use Traversable;

/**
 * Config class.
 *
 * @implements IteratorAggregate<string|int>
 * @implements ArrayAccess<string|int, mixed>
 */
if (PHP_VERSION_ID >= 80100) {
    class Config extends BaseConfig implements ArrayAccess, IteratorAggregate, Serializable, Countable
    {
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
        public function getIterator(): Traversable
        {
            return new ArrayIterator($this->config);
        }
    }
} else {
    class Config extends BaseConfig implements ArrayAccess, IteratorAggregate, Serializable, Countable
    {
        /**
         * Checks if the specified offset exists.
         *
         * @param string|int $offset The offset to check.
         *
         * @return bool True if the offset exists, false otherwise.
         */
        public function offsetExists($offset): bool
        {
            return $this->has((string) $offset);
        }

        /**
         * Retrieves the value at the specified offset.
         *
         * @param string|int $offset The offset of the value to retrieve.
         *
         * @return mixed The value associated with the given offset.
         */
        public function offsetGet($offset)
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
        public function offsetSet($offset, $value): void
        {
            $this->set((string) $offset, $value);
        }

        /**
         * Removes the value associated with the given offset.
         *
         * @param string|int $offset The offset of the value to remove.
         *
         * @return void
         */
        public function offsetUnset($offset): void
        {
            $this->unset((string) $offset);
        }

        /**
         * Counts the number of items in the config.
         *
         * @return int The number of items in the config.
         */
        public function count(): int
        {
            return count($this->toArray(), COUNT_RECURSIVE);
        }

        /**
         * Retrieves an external iterator.
         *
         * @return \Traversable An iterator implementing the Traversable interface,
         *                      allowing iteration over the configuration items.
         */
        public function getIterator(): Traversable
        {
            return new ArrayIterator($this->toArray());
        }
    }
}
