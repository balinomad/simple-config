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

The `Config` object is **immutable**. This means methods like `with`, `without`, `append`, and `merge` do not change the original object; they return a new, modified `Config` instance.


```php
use BaliNomad\SimpleConfig\Config;

$options = [
    'allowed_pets' => ['dog', 'cat', 'spider'],
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
    'has_spider' => true
];

$config = new Config($options);

// with(), without(), etc. return a NEW Config instance.
$newConfig = $config
    ->with('has_spider', false)                 // Set a value
    ->without('dog.color.tail')                 // Remove a value
    ->append('cat.food', 'salmon')              // Add an item to an array
    ->subtract('allowed_pets', 'spider');       // Remove an item from an array

// Get values using dot notation
$catFood = $newConfig->get('cat.food');
// Returns: ['tuna', 'chicken', 'lamb', 'salmon']

// Check if a key exists
$hasTailColor = $newConfig->has('dog.color.tail');
// Returns: false

// The original $config object remains unchanged
$originalSpiderSetting = $config->get('has_spider');
// Returns: true

// Get the entire configuration as an array
$arrConfig = $newConfig->toArray();
```

## 5. API Reference

| Method | Attributes | Returns | Description |
| :----- | :--------- | :------ | :---------- |
| *constructor* | `?array $config`, `int $cleaningFlags` | `self` | Creates a new Config instance. |
| `with` | `$key`, `$value` | `self` | Returns a **new** instance with a value set. |
| `without` | `$key` | `self` | Returns a **new** instance with a key removed. |
| `get` | `$key`, `$default` | `mixed` | Retrieves a value using dot notation. |
| `has` | `$key` | `bool` | Checks if a key exists. |
| `append` | `$key`, `$value` | `self` | Returns a **new** instance with a value appended to an array. |
| `subtract`| `$key`, `$value` | `self` | Returns a **new** instance with a value removed from an array. |
| `merge` | `$config`, `$method` | `self` | Returns a **new** instance merged with another configuration. |
| `split` | `$key` | `self` | Returns a **new** instance containing only a subset of the config. |
| `toArray` | - | `array` | Returns the entire configuration as an array. |
| `count` | - | `int` | Counts all leaf configuration values. Non-associative arrays are counted as a single leaf. |
| `getIterator`| - | `Traversable`| Gets an iterator for the top-level items. |
| `offsetExists`| `$offset` | `bool` | Implements `ArrayAccess`. Checks if a key exists (e.g., `isset($config['app.key'])`). |
| `offsetGet`| `$offset` | `mixed` | Implements `ArrayAccess`. Gets a value (e.g., `$config['app.key']`). |

**Note**: Modifying a `Config` object via array access (e.g., `$config['key'] = 'value'`) is not permitted and will throw a `LogicException`. Use the `with()` and `without()` methods instead.
