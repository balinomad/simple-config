# Simple config [![Latest Version](https://img.shields.io/github/release/navindex/simple-config?sort=semver&label=version)](https://raw.githubusercontent.com/navindex/simple-config/master/CHANGELOG.md)

[![Unit tests](https://github.com/navindex/simple-config/actions/workflows/test.yml/badge.svg?branch=master)](https://github.com/navindex/simple-config/actions/workflows/test.yml)
[![Code analysis](https://github.com/navindex/simple-config/actions/workflows/analysis.yml/badge.svg)](https://github.com/navindex/simple-config/actions/workflows/analysis.yml)
[![Build Status](https://app.travis-ci.com/navindex/simple-config.svg?branch=master)](https://app.travis-ci.com/navindex/simple-config)
[![Coverage Status](https://coveralls.io/repos/github/navindex/simple-config/badge.svg?branch=master)](https://coveralls.io/github/navindex/simple-config?branch=master)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue)](https://opensource.org/licenses/MIT)

## 1. What Is It

**Simple config** is a class to work with configuration settings. It helps you to perform actions like add, remove, check, append, subtract etc. by using dot notation keys.

## 2. What Is It Not

This library does not read the filesystem or other environment settings. To use an _.env_ file to feed **Simple config**, use it together with [phpdotenv](https://github.com/vlucas/phpdotenv) or other similar library.

## 3. Installation

This package can be installed through [Composer](https://getcomposer.org/).

```bash
composer require navindex/simple-config
```

## 4. Usage

```php
use Navindex\SimpleConfig\Config;

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

| Method        | Attributes           | Returns | Description                                                    |
| :------------ | :------------------- | :------ | :------------------------------------------------------------- |
| _constructor_ | $config              | -       | Constructor                                                    |
| set           | $key, $value         | self    | Saves a key value.                                             |
| unset         | $key                 | self    | Completely removes a key.                                      |
| get           | $key, $default       | mixed   | Retrieves a key value.                                         |
| has           | $key                 | boolean | Checks if a key exists and not null.                           |
| append        | $key, $value         | self    | Appends value(s) to an array.                                  |
| subtract      | $key, $value         | self    | Substract value(s) from an array.                              |
| merge         | $config, $method     | self    | Merges another config into this one.                           |
| split         | $key                 | Config  | Splits a sub-array of configuration options into a new config. |
| toArray       | -                    | array   | Returns the entire configuration as an array.                  |
| serialize     | -                    | string  | Generates a storable representation of the configuration.      |
| unserialize   | $data                | -       | Sets the configuration from a stored representation.           |
| count         | -                    | int     | Counts the config items.                                       |
| wrap          | $value               | self    | _Static._ If the given value is not an array, wraps it in one. |
| isAssoc       | $array               | boolean | _Static._ Tests if the array is associative.                   |
| commonKeys    | $array1, $array2,... | array   | _Static._ Returns the keys present in all arrays.              |

## 6. About Navindex

Navindex is a web development agency in Melbourne, Australia. You'll find an overview of our cmpany [on our website](https://www.navindex.com.au).
