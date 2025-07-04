# Simple config [![Latest Version](https://img.shields.io/github/release/balinomad/simple-config?sort=semver&label=version)](https://raw.githubusercontent.com/balinomad/simple-config/master/CHANGELOG.md)

[![Unit tests](https://github.com/balinomad/simple-config/actions/workflows/test.yml/badge.svg?branch=master)](https://github.com/balinomad/simple-config/actions/workflows/test.yml)
[![Code analysis](https://github.com/balinomad/simple-config/actions/workflows/analysis.yml/badge.svg)](https://github.com/balinomad/simple-config/actions/workflows/analysis.yml)
[![Coverage Status](https://coveralls.io/repos/github/balinomad/simple-config/badge.svg?branch=master)](https://coveralls.io/github/balinomad/simple-config?branch=master)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue)](https://opensource.org/licenses/MIT)

## 1. What Is It

**Simple config** is a class to work with configuration settings. It helps you to perform actions like add, remove, check, append, subtract etc. by using dot notation keys.

## 2. What Is It Not

This library does not read the filesystem or other environment settings. To use an _.env_ file to feed **Simple config**, use it together with [phpdotenv](https://github.com/vlucas/phpdotenv) or other similar library.

## 3. Installation

This package can be installed through [Composer](https://getcomposer.org/).

```bash
composer require balinomad/simple-config
```

## 4. Usage

```php
use BaliNomad\SimpleConfig\Config;

$options = [
    'number of fingers' => 5,
    'allowed pets' => ['dog', 'cat', 'spider'],
    'cat' => [
        'name' => 'Mia',
        'food' => ['tuna', 'chicken', 'lamb'],
    ],
    'dog' => [
        'name' => 'Bless',
        'color' => [
            'body' => 'white',
            'tail' => 'black',
        ]
    ],
    'spider' => true,
    42,
    'some text'
];

$config = new Config($options);

$config
    ->set('spider', false)
    ->unset('dog.color.tail')
    ->append('cat.food', 'salmon')
    ->subtract('cat.food', 'tuna');

$spider = $config->get('spider');

$doWeHaveDog = $config->has('dog');

$arrConfig = $config->toArray();
```

## 5. Actions

| Method | Attributes | Returns | Description |
| :----- | :--------- | :------ | :---------- |
| _constructor_ | $config | - | Constructor. |
| get | $key, $default | mixed | Retrieves a configuration value using dot notation. |
| set | $key, $value | self | Sets a configuration value using dot notation. |
| has | $key | boolean | Checks if a key exists using dot notation. |
| unset | $key | self | Removes a key using dot notation and cleans up empty parent arrays. |
| append | $key, $value | self | Appends value(s) to an array at the specified key. |
| subtract | $key, $value | self | Subtracts value(s) from an array at the specified key. |
| merge | $config, $method | self | Merges another configuration array or Config object. |
| split | $key | Config | Returns a new Config instance for a specific key. |
| toArray | - | array | Returns the entire configuration as an array. |
| __serialize | - | string | Magic method for serializing the object. |
| __unserialize | $data | - | Magic method for restoring the configuration from a given serialized array. |
| offsetExists | $offset | bool | Checks if the specified offset exists. |
| offsetGet | $offset | mixed | Retrieves the value at the specified offset. |
| offsetSet | $offset, $value | void | Sets the value at the specified offset. |
| offsetUnset | $offset | void | Removes the value associated with the given offset. |
| count | - | int | Counts all leaf configuration values. |
| getIterator | - | Traversable | An iterator implementing the Traversable interface, allowing iteration over the configuration items. |
| wrap | $value | array | _Static._ Wraps a value in an array unless it is already an array. |
| isAssoc | $array | boolean | _Static._ Heuristically determines if an array is associative. |
