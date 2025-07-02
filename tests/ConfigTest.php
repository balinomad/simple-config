<?php

declare(strict_types=1);

namespace BaliNomad\SimpleConfig\Tests;

use ArrayIterator;
use Iterator;
use BaliNomad\SimpleConfig\Config;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BaliNomad\SimpleConfig\Config
 */
final class ConfigTest extends TestCase
{
    /**
     * @dataProvider providerConfig
     *
     * @param  null|mixed[] $config
     * @param  mixed[]      $expected
     * @return void
     */
    public function testConstructor(?array $config, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->toArray());
    }

    /**
     * @dataProvider providerSet
     *
     * @param  string     $key
     * @param  mixed|null $value
     * @param  mixed[]    $expected
     * @return void
     */
    public function testSet(string $key, $value, array $expected)
    {
        $c = new Config();
        $this->assertEquals($expected, $c->set($key, $value)->toArray());
    }

    /**
     * @dataProvider providerUnset
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  mixed[]      $expected
     * @return void
     */
    public function testUnset(?array $config, string $key, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->unset($key)->toArray());
    }

    /**
     * @dataProvider providerGet
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  null|mixed   $default
     * @param  null|mixed   $expected
     * @return void
     */
    public function testGet(?array $config, string $key, $default, $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->get($key, $default));
    }

    /**
     * @dataProvider providerHas
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  bool         $expected
     * @return void
     */
    public function testHas(?array $config, string $key, bool $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->has($key));
    }

    /**
     * @dataProvider providerAppend
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  null|mixed   $value
     * @param  mixed[]      $expected
     * @return void
     */
    public function testAppend(?array $config, string $key, $value, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->append($key, $value)->toArray());
    }

    /**
     * @dataProvider providerSubtract
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  mixed|null   $value
     * @param  mixed[]      $expected
     * @return void
     */
    public function testSubtract(?array $config, string $key, $value, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->subtract($key, $value)->toArray());
    }

    /**
     * @dataProvider providerMerge
     *
     * @param  null|mixed[]                                $config
     * @param  null|mixed[]|\BaliNomad\SimpleConfig\Config $merge
     * @param  mixed[]                                     $expected
     * @return void
     */
    public function testMerge(?array $config, $merge, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->merge($merge)->toArray());
    }

    /**
     * @dataProvider providerMerge
     *
     * @param  null|mixed[]                                $config
     * @param  null|mixed[]|\BaliNomad\SimpleConfig\Config $merge
     * @param  mixed[]                                     $expected
     * @return void
     */
    public function testMergeReplace(?array $config, $merge, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->merge($merge, Config::MERGE_REPLACE)->toArray());
    }

    /**
     * @dataProvider providerMergeKeep
     *
     * @param  null|mixed[] $config
     * @param  null|mixed[] $merge
     * @param  mixed[]      $expected
     * @return void
     */
    public function testMergeKeep(?array $config, ?array $merge, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->merge($merge, Config::MERGE_KEEP)->toArray());
    }

    /**
     * @dataProvider providerMergeAppend
     *
     * @param  null|mixed[] $config
     * @param  null|mixed[] $merge
     * @param  mixed[]      $expected
     * @return void
     */
    public function testMergeAppend(?array $config, ?array $merge, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->merge($merge, Config::MERGE_APPEND)->toArray());
    }

    /**
     * @dataProvider providerSplit
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  mixed[]      $expected
     * @return void
     */
    public function testSplit(?array $config, string $key, array $expected)
    {
        $c = new Config($config);
        $this->assertEquals(new Config($expected), $c->split($key));
    }

    /**
     * @dataProvider providerCount
     *
     * @param  null|mixed[] $config
     * @param  int          $expected
     * @return void
     */
    public function testCount(?array $config, int $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->count());
    }

    /**
     * @dataProvider providerConfig
     *
     * @param  null|mixed[] $config
     * @param  mixed[]      $expected
     * @return void
     */
    public function testSerialize(?array $config, array $expected)
    {
        $c = new Config($config);
        $data = $c->serialize();

        $cNew = new Config();
        $cNew->unserialize($data);

        $this->assertEquals($expected, $c->toArray());
    }

    /**
     * @dataProvider providerConfig
     *
     * @param  null|mixed[] $config
     * @param  mixed[]      $expected
     * @return void
     */
    public function testIterator(?array $config, array $expected)
    {
        $c = new Config($config);

        $this->assertEquals(new ArrayIterator($expected), $c->getIterator());
    }

    /**
     * @dataProvider providerSet
     *
     * @param  string     $key
     * @param  mixed|null $value
     * @param  mixed[]    $expected
     * @return void
     */
    public function testArrayAccessSet(string $key, $value, array $expected)
    {
        $c = new Config();
        $c[$key] = $value;
        $this->assertEquals($expected, $c->toArray());
    }

    /**
     * @dataProvider providerUnset
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  mixed[]      $expected
     * @return void
     */
    public function testArrayAccessUnset(?array $config, string $key, array $expected)
    {
        $c = new Config($config);
        unset($c[$key]);
        $this->assertEquals($expected, $c->toArray());
    }

    /**
     * @dataProvider providerArrayAccessGet
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  null|mixed   $expected
     * @return void
     */
    public function testArrayAccessGet(?array $config, string $key, $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c[$key]);
    }

    /**
     * @dataProvider providerHas
     *
     * @param  null|mixed[] $config
     * @param  string       $key
     * @param  bool         $expected
     * @return void
     */
    public function testArrayAccessExists(?array $config, string $key, bool $expected)
    {
        $c = new Config($config);
        $this->assertEquals($expected, isset($c[$key]));
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, array<int, mixed>>
     */
    public function providerConfig(): Iterator
    {
        yield [null, []];
        yield [[], []];
        yield [
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'something',
                'ddd' => ['xxx', 'yyy', 'zz'],
                'eee' => -8,
                'fff' => false,
                'ggg' => null,
                42,
            ],
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                0 => 'something',
                'ddd' => ['xxx', 'yyy', 'zz'],
                'eee' => -8,
                'fff' => false,
                'ggg' => null,
                1 => 42,
            ],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, array<int, mixed>>
     */
    public function providerSet(): Iterator
    {
        yield ['aaa.bbb.ccc', 'value', ['aaa' => ['bbb' => ['ccc' => 'value']]]];
        yield ['aaa.bbb.ccc', null, ['aaa' => ['bbb' => ['ccc' => null]]]];
        yield ['aaa.bbb.ccc', false, ['aaa' => ['bbb' => ['ccc' => false]]]];
        yield ['aaa.bbb.ccc', [], ['aaa' => ['bbb' => ['ccc' => []]]]];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed>
     */
    public function providerUnset(): Iterator
    {
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ccc', ['aaa' => ['bbb' => ['ccc' => null]]]];
        yield [['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]], 'aaa.bbb.ccc', ['aaa' => ['bbb' => ['ccc' => null]]]];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ddd', ['aaa' => ['bbb' => ['ccc' => 'value']]]];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb', ['aaa' => ['bbb' => null]]];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa', ['aaa' => null]];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ddd', ['aaa' => ['bbb' => ['ccc' => 'value']]]];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerGet(): Iterator
    {
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ccc', 'default', 'value'];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb', 'default', ['ccc' => 'value']];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa', 'default', ['bbb' => ['ccc' => 'value']]];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.', 'default', 'default'];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ddd', 'default', 'default'];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.ddd', null, null];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerHas(): Iterator
    {
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ccc', true];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb', true];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa', true];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.', false];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ddd', false];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.ddd', false];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerAppend(): Iterator
    {
        yield [
            ['aaa' => ['bbb' => ['ccc' => ['value']]]],
            'aaa.bbb.ccc',
            'another value',
            ['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => ['value']]]],
            'aaa.bbb.ccc',
            ['another value', 'new value'],
            ['aaa' => ['bbb' => ['ccc' => ['value', 'another value', 'new value']]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            'ddd',
            'another value',
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['another value'],
            ],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => ['value']]]],
            'aaa.ddd',
            'another value',
            [
                'aaa' => [
                    'bbb' => ['ccc' => ['value']],
                    'ddd' => ['another value'],
                ],
            ],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            'aaa.bbb.ccc',
            'another value',
            ['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerSubtract(): Iterator
    {
        yield [
            ['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]],
            'aaa.bbb.ccc',
            'value',
            ['aaa' => ['bbb' => ['ccc' => ['another value']]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]],
            'aaa.bbb.ccc',
            'non-existent value',
            ['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => ['value']]]],
            'aaa.bbb.ccc',
            'value',
            ['aaa' => ['bbb' => ['ccc' => []]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            'aaa.bbb.ccc',
            'value',
            ['aaa' => ['bbb' => ['ccc' => []]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => []]]],
            'aaa.bbb.ccc',
            'value',
            ['aaa' => ['bbb' => ['ccc' => []]]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            'aaa.bbb.ddd',
            'value',
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value', 'ddd' => 'another value']]],
            'aaa.bbb',
            'value',
            ['aaa' => ['bbb' => ['ddd' => 'another value']]],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerMerge(): Iterator
    {
        yield [null, null, []];
        yield [[], null, []];
        yield [null, [], []];
        yield [[], [], []];
        yield [[], new Config(), []];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            ['ddd' => ['eee' => ['fff' => 'another value']]],
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            new Config(['ddd' => ['eee' => ['fff' => 'another value']]]),
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            ['aaa' => ['bbb' => 'just a value']],
            ['aaa' => ['bbb' => 'just a value']],
        ];
        yield [
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'another value']],
            ['aaa' => ['bbb' => 'another value']],
        ];
        yield [
            [
                'aaa' => [
                    'bbb' => 'value',
                    'ccc' => 'another value',
                    'ddd' => ['value1', 'value2', 'value3'],
                ],
            ],
            [
                'aaa' => [
                    'bbb' => 'new value',
                    'ddd' => ['value4'],
                    'eee' => 'just a value',
                ],
            ],
            [
                'aaa' => [
                    'bbb' => 'new value',
                    'ccc' => 'another value',
                    'ddd' => ['value4'],
                    'eee' => 'just a value',
                ],
            ],
        ];
        yield [
            [],
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'value']],
        ];
        yield [
            ['aaa' => []],
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'value']],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerMergeKeep(): Iterator
    {
        yield [null, null, []];
        yield [[], null, []];
        yield [null, [], []];
        yield [[], [], []];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            ['ddd' => ['eee' => ['fff' => 'another value']]],
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            ['aaa' => ['bbb' => 'just a value']],
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
        ];
        yield [
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'another value']],
            ['aaa' => ['bbb' => 'value']],
        ];
        yield [
            [
                'aaa' => [
                    'bbb' => 'value',
                    'ccc' => 'another value',
                    'ddd' => ['value1', 'value2', 'value3'],
                ],
            ],
            [
                'aaa' => [
                    'bbb' => 'new value',
                    'ddd' => ['value4'],
                    'eee' => 'just a value',
                ],
            ],
            [
                'aaa' => [
                    'bbb' => 'value',
                    'ccc' => 'another value',
                    'ddd' => ['value1', 'value2', 'value3'],
                    'eee' => 'just a value',
                ],
            ],
        ];
        yield [
            [],
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'value']],
        ];
        yield [
            ['aaa' => []],
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'value']],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerMergeAppend(): Iterator
    {
        yield [null, null, []];
        yield [[], null, []];
        yield [null, [], []];
        yield [[], [], []];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            ['ddd' => ['eee' => ['fff' => 'another value']]],
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
        ];
        yield [
            ['aaa' => ['bbb' => ['ccc' => 'value']]],
            ['aaa' => ['bbb' => 'just a value']],
            ['aaa' => ['bbb' => ['just a value', 'ccc' => 'value']]],
        ];
        yield [
            ['aaa' => ['bbb' => 'value']],
            ['aaa' => ['bbb' => 'another value']],
            ['aaa' => ['bbb' => ['value', 'another value']]],
        ];
        yield [
            [
                'aaa' => [
                    'bbb' => 'value',
                    'ccc' => 'another value',
                    'ddd' => ['value1', 'value2', 'value3'],
                ],
            ],
            [
                'aaa' => [
                    'bbb' => 'new value',
                    'ddd' => ['value4'],
                    'eee' => 'just a value',
                ],
            ],
            [
                'aaa' => [
                    'bbb' => ['value', 'new value'],
                    'ccc' => 'another value',
                    'ddd' => ['value1', 'value2', 'value3', 'value4'],
                    'eee' => 'just a value',
                ],
            ],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerSplit(): Iterator
    {
        yield [null, 'any', []];
        yield [null, 'multiple.level.key', []];
        yield [
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
            'aaa',
            'aaa' => ['bbb' => ['ccc' => 'value']],
        ];
        yield [
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
            'eee',
            [],
        ];
        yield [
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'ddd' => ['eee' => ['fff' => 'another value']],
            ],
            'ddd.eee',
            'eee' => ['fff' => 'another value'],
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, array<int, mixed>>
     */
    public function providerCount(): Iterator
    {
        yield [null, 0];
        yield [[], 0];
        yield [
            [
                'aaa' => ['bbb' => ['ccc' => 'value']],
                'something',
                'ddd' => ['xxx', 'yyy', 'zz'],
                'eee' => -8,
                'fff' => false,
                'ggg' => null,
                42,
            ],
            12,
        ];
    }

    /**
     * Data provider.
     *
     * @return \Iterator <int, mixed[]>
     */
    public function providerArrayAccessGet(): Iterator
    {
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ccc', 'value'];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb', ['ccc' => 'value']];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa', ['bbb' => ['ccc' => 'value']]];
        yield [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.ddd', null];
    }
}
