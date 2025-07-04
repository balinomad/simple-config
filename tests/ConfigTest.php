<?php

declare(strict_types=1);

namespace BaliNomad\SimpleConfig\Tests;

use BaliNomad\SimpleConfig\Config;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BaliNomad\SimpleConfig\Config
 */
final class ConfigTest extends TestCase
{
    public function testConstructor(): void
    {
        $c = new Config([
            'aaa' => ['bbb' => ['ccc' => 'value']],
            'something',
            'ddd' => ['xxx', 'yyy', 'zz'],
            'eee' => -8,
            'fff' => false,
            'ggg' => null, // Should be removed by constructor
            42,
            'hhh' => ['a' => null], // Should be removed
            'iii' => [], // should be removed
        ]);

        $expected = [
            'aaa' => ['bbb' => ['ccc' => 'value']],
            0 => 'something',
            'ddd' => [0 => 'xxx', 1 => 'yyy', 2 => 'zz'],
            'eee' => -8,
            'fff' => false,
            1 => 42,
        ];

        $this->assertEquals($expected, $c->toArray());
        $this->assertEquals(new Config(), new Config(null));
        $this->assertEquals([], (new Config([]))->toArray());
    }

    /**
     * @dataProvider providerSet
     */
    public function testSet(string $key, mixed $value, array $expected): void
    {
        $c = new Config();
        $this->assertEquals($expected, $c->set($key, $value)->toArray());
    }

    public static function providerSet(): \Generator
    {
        yield 'set nested value' => ['aaa.bbb.ccc', 'value', ['aaa' => ['bbb' => ['ccc' => 'value']]]];
        yield 'set null unsets key' => ['aaa.bbb.ccc', null, []];
        yield 'set false value' => ['aaa.bbb.ccc', false, ['aaa' => ['bbb' => ['ccc' => false]]]];
        yield 'set empty array' => ['aaa.bbb.ccc', [], ['aaa' => ['bbb' => ['ccc' => []]]]];
        yield 'set overwrites scalar' => ['a', 'string', ['a' => 'string']];
    }

    /**
     * @dataProvider providerUnset
     */
    public function testUnset(?array $config, string $key, array $expected): void
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->unset($key)->toArray());
    }

    public static function providerUnset(): \Generator
    {
        yield 'unset leaf node' => [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ccc', []];
        yield 'unset array node' => [['aaa' => ['bbb' => ['ccc' => ['value', 'another value']]]], 'aaa.bbb.ccc', []];
        yield 'unset non-existent leaf' => [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb.ddd', ['aaa' => ['bbb' => ['ccc' => 'value']]]];
        yield 'unset intermediate node' => [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa.bbb', []];
        yield 'unset root node' => [['aaa' => ['bbb' => ['ccc' => 'value']]], 'aaa', []];
        yield 'unset non-existent deep' => [['aaa' => ['bbb' => ['ccc' => 'value']]], 'x.y.z', ['aaa' => ['bbb' => ['ccc' => 'value']]]];
        yield 'unset with empty key' => [['a' => 1], '', ['a' => 1]];
    }

    /**
     * @dataProvider providerGet
     */
    public function testGet(?array $config, string $key, mixed $default, mixed $expected): void
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->get($key, $default));
    }

    public static function providerGet(): \Generator
    {
        $conf = ['aaa' => ['bbb' => ['ccc' => 'value']], 'scalar' => 'string'];
        yield 'get existing leaf' => [$conf, 'aaa.bbb.ccc', 'default', 'value'];
        yield 'get existing node' => [$conf, 'aaa.bbb', 'default', ['ccc' => 'value']];
        yield 'get root node' => [$conf, 'aaa', 'default', ['bbb' => ['ccc' => 'value']]];
        yield 'get with trailing dot' => [$conf, 'aaa.bbb.', 'default', ['ccc' => 'value']];
        yield 'get non-existent leaf' => [$conf, 'aaa.bbb.ddd', 'default', 'default'];
        yield 'get non-existent with null default' => [$conf, 'aaa.ddd', null, null];
        yield 'get through scalar' => [$conf, 'scalar.foo', 'default', 'default'];
    }

    /**
     * @dataProvider providerHas
     */
    public function testHas(?array $config, string $key, bool $expected): void
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->has($key));
    }

    public static function providerHas(): \Generator
    {
        $conf = ['aaa' => ['bbb' => ['ccc' => 'value']], 'scalar' => 'string'];
        yield 'has existing leaf' => [$conf, 'aaa.bbb.ccc', true];
        yield 'has existing node' => [$conf, 'aaa.bbb', true];
        yield 'has root node' => [$conf, 'aaa', true];
        yield 'has with trailing dot' => [$conf, 'aaa.', true];
        yield 'has non-existent leaf' => [$conf, 'aaa.bbb.ddd', false];
        yield 'has non-existent root' => [$conf, 'xxx', false];
        yield 'has through scalar' => [$conf, 'scalar.foo', false];
    }

    /**
     * @dataProvider providerAppend
     */
    public function testAppend(?array $config, string $key, mixed $value, array $expected): void
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->append($key, $value)->toArray());
    }

    public static function providerAppend(): \Generator
    {
        yield 'append to existing array' => [
            ['a' => ['b' => ['c' => ['v1']]]],
            'a.b.c',
            'v2',
            ['a' => ['b' => ['c' => ['v1', 'v2']]]]
        ];
        yield 'append array to existing array' => [
            ['a' => ['b' => ['c' => ['v1']]]],
            'a.b.c',
            ['v2', 'v3'],
            ['a' => ['b' => ['c' => ['v1', 'v2', 'v3']]]]
        ];
        yield 'append to new key' => [
            ['a' => 1],
            'b',
            'v2',
            ['a' => 1, 'b' => ['v2']]
        ];
        yield 'append to scalar converts to array' => [
            ['a' => ['b' => 'v1']],
            'a.b',
            'v2',
            ['a' => ['b' => ['v1', 'v2']]]
        ];
    }

    /**
     * @dataProvider providerSubtract
     */
    public function testSubtract(?array $config, string $key, mixed $value, array $expected): void
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->subtract($key, $value)->toArray());
    }

    public static function providerSubtract(): \Generator
    {
        yield 'subtract from list' => [
            ['a' => ['v1', 'v2', 'v3']],
            'a',
            'v2',
            ['a' => ['v1', 'v3']]
        ];
        yield 'subtract from assoc' => [
            ['a' => ['k1' => 'v1', 'k2' => 'v2']],
            'a',
            'v1',
            ['a' => ['k2' => 'v2']]
        ];
        yield 'subtract last item removes key' => [
            ['a' => ['v1']],
            'a',
            'v1',
            []
        ];
        yield 'subtract from non-array does nothing' => [
            ['a' => 'string'],
            'a',
            'string',
            ['a' => 'string']
        ];
        yield 'subtract from non-existent key does nothing' => [
            ['a' => 1],
            'b',
            'c',
            ['a' => 1]
        ];
        yield 'subtract null value does nothing' => [
            ['a' => ['v1', 'v2']],
            'a',
            null,
            ['a' => ['v1', 'v2']]
        ];
    }

    /**
     * @dataProvider providerMerge
     */
    public function testMerge(?array $config, $merge, int $method, array $expected): void
    {
        $c = new Config($config);
        $this->assertEquals($expected, $c->merge($merge, $method)->toArray());
    }

    public static function providerMerge(): \Generator
    {
        $base = [
            'a' => 1,
            'b' => ['c' => 2, 'd' => [3, 4]],
            'e' => [5, 6]
        ];
        $replacement = [
            'b' => ['c' => 99, 'd' => [98]], // 'd' is a list, should be replaced entirely
            'e' => [7], // 'e' is a list, should be replaced
            'f' => 8
        ];

        yield 'replace strategy' => [
            $base,
            $replacement,
            Config::MERGE_REPLACE,
            ['a' => 1, 'b' => ['c' => 99, 'd' => [98]], 'e' => [7], 'f' => 8]
        ];
        yield 'keep strategy' => [
            $base,
            $replacement,
            Config::MERGE_KEEP,
            ['a' => 1, 'b' => ['c' => 2, 'd' => [3, 4]], 'e' => [5, 6], 'f' => 8]
        ];
        yield 'append strategy' => [
            $base,
            $replacement,
            Config::MERGE_APPEND,
            // 'c' is replaced, 'd' and 'e' (lists) are merged, 'f' is added
            ['a' => 1, 'b' => ['c' => 99, 'd' => [3, 4, 98]], 'e' => [5, 6, 7], 'f' => 8]
        ];
        yield 'merge with Config object' => [
            ['a' => 1],
            new Config(['b' => 2]),
            Config::MERGE_REPLACE,
            ['a' => 1, 'b' => 2]
        ];
        yield 'merge with null' => [
            ['a' => 1],
            null,
            Config::MERGE_REPLACE,
            ['a' => 1]
        ];
    }

    /**
     * @dataProvider providerSplit
     */
    public function testSplit(?array $config, string $key, array $expected): void
    {
        $c = new Config($config);
        $this->assertEquals(new Config($expected), $c->split($key));
    }

    public static function providerSplit(): \Generator
    {
        $conf = [
            'a' => ['b' => ['c' => 'value']],
            'd' => ['e' => ['f' => 'another']],
            'g' => 'scalar'
        ];
        yield 'split existing node' => [$conf, 'a.b', ['c' => 'value']];
        yield 'split non-existent node' => [$conf, 'a.x', []];
        yield 'split scalar value' => [$conf, 'g', [0 => 'scalar']];
    }

    public function testArrayAccess(): void
    {
        $c = new Config();
        $c['a.b'] = 'value';
        $this->assertEquals(['a' => ['b' => 'value']], $c->toArray());
        $this->assertTrue(isset($c['a.b']));
        $this->assertEquals('value', $c['a.b']);
        unset($c['a.b']);
        $this->assertFalse(isset($c['a.b']));
        $this->assertEquals([], $c->toArray());
    }

    public function testCount(): void
    {
        $c = new Config([
            'a' => ['b' => 'c'], // 1 leaf (a.b)
            'd' => [1, 2, 3],    // 1 leaf (d is a list)
            'e' => 'f'           // 1 leaf (e)
        ]);
        $this->assertCount(3, $c);
        $this->assertCount(0, new Config());
    }

    public function testInterfaces(): void
    {
        $c = new Config(['a' => 1, 'b' => 2]);
        $this->assertInstanceOf(\Countable::class, $c);
        $this->assertInstanceOf(\ArrayAccess::class, $c);
        $this->assertInstanceOf(\IteratorAggregate::class, $c);

        $items = [];
        foreach ($c as $key => $value) {
            $items[$key] = $value;
        }
        $this->assertEquals(['a' => 1, 'b' => 2], $items);
    }

    public function testSerialization(): void
    {
        $c = new Config(['a' => 1, 'b' => ['c' => true]]);
        $serialized = serialize($c);
        /** @var Config $unserialized */
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Config::class, $unserialized);
        $this->assertEquals($c->toArray(), $unserialized->toArray());
    }

    public function testMergeWithUnserializedObject(): void
    {
        $c1 = new Config(['a' => 1]);

        $c2_orig = new Config(['b' => 2]);
        $serialized = serialize($c2_orig);
        /** @var Config $c2_unserialized */
        $c2_unserialized = unserialize($serialized);

        $c1->merge($c2_unserialized);

        $this->assertEquals(['a' => 1, 'b' => 2], $c1->toArray());
    }
}
