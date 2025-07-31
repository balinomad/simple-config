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
    public function testConstructorWithNull(): void
    {
        $c = new Config(null);
        self::assertEquals([], $c->toArray());
    }

    public function testConstructorDefaultCleaning(): void
    {
        // Default is CLEAN_NULLS, so empty arrays should be kept.
        $c = new Config([
            'aaa' => ['bbb' => ['ccc' => 'value']],
            'something',
            'ddd' => ['xxx', 'yyy', 'zz'],
            'eee' => -8,
            'fff' => false,
            'ggg' => null, // Should be removed
            42,
            'hhh' => ['a' => null], // 'a' should be removed, 'hhh' becomes empty array
            'iii' => [], // Should be kept
        ]);

        $expected = [
            'aaa' => ['bbb' => ['ccc' => 'value']],
            0 => 'something',
            'ddd' => [0 => 'xxx', 1 => 'yyy', 2 => 'zz'],
            'eee' => -8,
            'fff' => false,
            1 => 42,
            'hhh' => [],
            'iii' => [],
        ];

        self::assertEquals($expected, $c->toArray());
    }

    public function testConstructorWithCleanAll(): void
    {
        // CLEAN_ALL should remove both nulls and empty arrays.
        $c = new Config([
            'aaa' => ['bbb' => ['ccc' => 'value']],
            'ggg' => null,
            'hhh' => ['a' => null],
            'iii' => [],
        ], Config::CLEAN_ALL);

        $expected = [
            'aaa' => ['bbb' => ['ccc' => 'value']],
        ];

        self::assertEquals($expected, $c->toArray());
    }

    public function testConstructorWithCleanNone(): void
    {
        // CLEAN_NONE should keep everything.
        $c = new Config([
            'a' => null,
            'b' => [],
        ], Config::CLEAN_NONE);

        self::assertEquals(['a' => null, 'b' => []], $c->toArray());
    }

    /**
     * @dataProvider providerWith
     *
     * @param array<string|int, mixed> $initial
     * @param array<string|int, mixed> $expected
     */
    public function testWith(string $key, mixed $value, int $flags, array $initial, array $expected): void
    {
        $c = new Config($initial, $flags);
        $newC = $c->with($key, $value);
        self::assertEquals($initial, $c->toArray(), 'Original config should not be modified.');
        self::assertEquals($expected, $newC->toArray(), 'New config should have the updated value.');
    }

    /**
     * @return \Generator<string, array{0: string, 1: mixed, 2: int, 3: array<string|int, mixed>, 4: array<string|int, mixed>}>
     */
    public static function providerWith(): \Generator
    {
        yield 'set nested value' => ['aaa.bbb.ccc', 'value', Config::CLEAN_NULLS, [], ['aaa' => ['bbb' => ['ccc' => 'value']]]];
        yield 'set null unsets key by default' => ['aaa.bbb.ccc', null, Config::CLEAN_NULLS, ['aaa' => ['bbb' => ['ccc' => 'old']]], ['aaa' => ['bbb' => []]]];
        yield 'set null with CLEAN_ALL unsets parents' => ['aaa.bbb.ccc', null, Config::CLEAN_ALL, ['aaa' => ['bbb' => ['ccc' => 'old']]], []];
        yield 'set null does not unset key with CLEAN_NONE' => ['aaa.bbb.ccc', null, Config::CLEAN_NONE, [], ['aaa' => ['bbb' => ['ccc' => null]]]];
        yield 'set empty array unsets key with CLEAN_ALL' => ['aaa.bbb.ccc', [], Config::CLEAN_ALL, ['aaa' => ['bbb' => ['ccc' => 'old']]], []];
        yield 'set empty array does not unset key with CLEAN_NULLS' => ['aaa.bbb.ccc', [], Config::CLEAN_NULLS, [], ['aaa' => ['bbb' => ['ccc' => []]]]];
        yield 'set false value' => ['aaa.bbb.ccc', false, Config::CLEAN_NULLS, [], ['aaa' => ['bbb' => ['ccc' => false]]]];
        yield 'set with empty key does nothing' => ['', 'value', Config::CLEAN_NULLS, ['existing' => 'data'], ['existing' => 'data']];
    }

    /**
     * @dataProvider providerWithout
     *
     * @param null|array<int|string, mixed> $config
     * @param array<int|string, mixed>      $expected
     */
    public function testWithout(?array $config, string $key, int $flags, array $expected): void
    {
        $c = new Config($config, $flags);
        $newC = $c->without($key);
        self::assertEquals($config ?? [], $c->toArray(), 'Original config should not be modified.');
        self::assertEquals($expected, $newC->toArray(), 'New config should have the key removed.');
    }

    /**
     * @return \Generator<string, array{0: null|array<int|string, mixed>, 1: string, 2: int, 3: array<string, mixed>}>
     */
    public static function providerWithout(): \Generator
    {
        $conf = ['aaa' => ['bbb' => ['ccc' => 'value']]];
        yield 'unset leaf node (default)' => [$conf, 'aaa.bbb.ccc', Config::CLEAN_NULLS, ['aaa' => ['bbb' => []]]];
        yield 'unset leaf node (with cleanup)' => [$conf, 'aaa.bbb.ccc', Config::CLEAN_ALL, []];
        yield 'unset non-existent leaf' => [['aaa' => 1], 'aaa.bbb', Config::CLEAN_ALL, ['aaa' => 1]];
        yield 'unset intermediate node' => [$conf, 'aaa.bbb', Config::CLEAN_ALL, []];
        yield 'unset root node' => [$conf, 'aaa', Config::CLEAN_ALL, []];
        yield 'unset with empty key does nothing' => [['a' => 1], '', Config::CLEAN_ALL, ['a' => 1]];
        yield 'unset non-existent key' => [['existing' => 'value'], 'nonexistent', Config::CLEAN_NULLS, ['existing' => 'value']];
        yield 'unset with empty key' => [['test' => 'value'], '', Config::CLEAN_NULLS, ['test' => 'value']];
    }

    /**
     * @dataProvider providerGet
     *
     * @param array<int|string, mixed>|null $config
     */
    public function testGet(?array $config, string $key, mixed $default, mixed $expected): void
    {
        $c = new Config($config);
        self::assertEquals($expected, $c->get($key, $default));
    }

    /**
     * @return \Generator<string, array{0: array<int|string, mixed>|null, 1: string, 2: mixed, 3: mixed}>
     */
    public static function providerGet(): \Generator
    {
        $conf = ['aaa' => ['bbb' => ['ccc' => 'value']], 'scalar' => 'string'];
        yield 'get existing leaf' => [$conf, 'aaa.bbb.ccc', 'default', 'value'];
        yield 'get existing node' => [$conf, 'aaa.bbb', 'default', ['ccc' => 'value']];
        yield 'get non-existent leaf' => [$conf, 'aaa.bbb.ddd', 'default', 'default'];
        yield 'get through scalar' => [$conf, 'scalar.foo', 'default', 'default'];
    }

    /**
     * @dataProvider providerHas
     *
     * @param array<int|string, mixed>|null $config
     */
    public function testHas(?array $config, string $key, bool $expected, int $flags = Config::CLEAN_NULLS): void
    {
        $c = new Config($config, $flags);
        self::assertEquals($expected, $c->has($key));
    }

    /**
     * @return \Generator<string, array{0: array<int|string, mixed>|null, 1: string, 2: bool, 3?: int}>
     */
    public static function providerHas(): \Generator
    {
        $conf = ['aaa' => ['bbb' => ['ccc' => 'value']], 'scalar' => 'string', 'n' => null];
        yield 'has existing leaf' => [$conf, 'aaa.bbb.ccc', true];
        yield 'has existing null with CLEAN_NONE' => [['n' => null], 'n', true, Config::CLEAN_NONE];
        yield 'does not have existing null with CLEAN_NULLS' => [['n' => null], 'n', false, Config::CLEAN_NULLS];
        yield 'has non-existent leaf' => [$conf, 'aaa.bbb.ddd', false];
    }

    /**
     * @dataProvider providerAppend
     *
     * @param array<int|string, mixed>|null $config
     * @param array<int|string, mixed>      $expected
     */
    public function testAppend(?array $config, string $key, mixed $value, array $expected): void
    {
        $c = new Config($config);
        $newC = $c->append($key, $value);

        self::assertEquals($config ?? [], $c->toArray(), 'Original config should not be modified.');
        self::assertEquals($expected, $newC->toArray(), 'New config should have the updated value.');
    }

    /**
     * @return \Generator<string, array{0: array<int|string, mixed>|null, 1: string, 2: mixed, 3: array<string, mixed>}>
     */
    public static function providerAppend(): \Generator
    {
        yield 'append to existing array' => [['a' => ['b' => [['v1']]]], 'a.b.0', 'v2', ['a' => ['b' => [['v1', 'v2']]]]];
        yield 'append to new key' => [['a' => 1], 'b', 'v2', ['a' => 1, 'b' => ['v2']]];
        yield 'append to scalar converts to array' => [['a' => ['b' => 'v1']], 'a.b', 'v2', ['a' => ['b' => ['v1', 'v2']]]];
    }

    /**
     * @dataProvider providerSubtract
     *
     * @param array<int|string, mixed>|null $config
     * @param array<int|string, mixed>      $expected
     */
    public function testSubtract(?array $config, string $key, mixed $value, int $flags, array $expected): void
    {
        $c = new Config($config, $flags);
        $newC = $c->subtract($key, $value);

        self::assertEquals($config ?? [], $c->toArray(), 'Original config should not be modified.');
        self::assertEquals($expected, $newC->toArray(), 'New config should have the updated value.');
    }

    /**
     * @return \Generator<string, array{0: array<int|string, mixed>|null, 1: string, 2: mixed, 3: int, 4: array<string, mixed>}>
     */
    public static function providerSubtract(): \Generator
    {
        yield 'subtract from list' => [['a' => ['v1', 'v2', 'v3']], 'a', 'v2', Config::CLEAN_NULLS, ['a' => [0 => 'v1', 1 => 'v3']]];
        yield 'subtract last item (default)' => [['a' => ['v1']], 'a', 'v1', Config::CLEAN_NULLS, ['a' => []]];
        yield 'subtract last item (with cleanup)' => [['a' => ['v1']], 'a', 'v1', Config::CLEAN_ALL, []];
        yield 'subtract from non-array does nothing' => [['a' => 's'], 'a', 's', Config::CLEAN_ALL, ['a' => 's']];
        yield 'subtract from non-existent key does nothing' => [['a' => 1], 'b', 'c', Config::CLEAN_ALL, ['a' => 1]];
        yield 'subtract from associative array' => [['tags' => ['php' => 'PHP', 'js' => 'JavaScript']], 'tags', 'PHP', Config::CLEAN_NULLS, ['tags' => ['js' => 'JavaScript']]];
    }

    /**
     * @dataProvider providerMerge
     *
     * @param array<string, mixed>|null        $config
     * @param array<string, mixed>|Config|null $merge
     * @param array<string, mixed>             $expected
     */
    public function testMerge(?array $config, mixed $merge, int $method, array $expected): void
    {
        $c = new Config($config);
        $newC = $c->merge($merge, $method);

        self::assertEquals($config ?? [], $c->toArray(), 'Original config should not be modified.');
        self::assertEquals($expected, $newC->toArray(), 'New config should have the updated values.');
    }

    /**
     * @return \Generator<string, array{0: array<int|string, mixed>|null, 1: mixed, 2: int, 3: array<string, mixed>}>
     */
    public static function providerMerge(): \Generator
    {
        $base = ['a' => 1, 'b' => ['c' => 2, 'd' => [3, 4]], 'e' => [5, 6]];
        $repl = ['b' => ['c' => 99, 'd' => [98]], 'e' => [7], 'f' => 8];

        yield 'replace strategy' => [$base, $repl, Config::MERGE_REPLACE, ['a' => 1, 'b' => ['c' => 99, 'd' => [98]], 'e' => [7], 'f' => 8]];
        yield 'keep strategy' => [$base, $repl, Config::MERGE_KEEP, ['a' => 1, 'b' => ['c' => 2, 'd' => [3, 4]], 'e' => [5, 6], 'f' => 8]];
        yield 'append strategy' => [$base, $repl, Config::MERGE_APPEND, ['a' => 1, 'b' => ['c' => 99, 'd' => [3, 4, 98]], 'e' => [5, 6, 7], 'f' => 8]];
        yield 'keep strategy with existing keys' => [['a' => 1, 'b' => 2], ['b' => 99, 'c' => 3], Config::MERGE_KEEP, ['a' => 1, 'b' => 2, 'c' => 3]];
        yield 'merge with Config object' => [
            ['a' => 1, 'b' => ['x' => 2]],
            new Config(['b' => ['x' => 9], 'c' => 3]),
            Config::MERGE_REPLACE,
            ['a' => 1, 'b' => ['x' => 9], 'c' => 3]
        ];
    }

    public function testSplitPreservesCleaningFlags(): void
    {
        // Create a config that cleans everything
        $c = new Config(['a' => ['b' => 'value', 'c' => null]], Config::CLEAN_ALL);
        self::assertEquals(['a' => ['b' => 'value']], $c->toArray());

        // Split it
        $split = $c->split('a');
        self::assertEquals(['b' => 'value'], $split->toArray());

        // Test the behavior of the new object to prove flags were inherited
        $newSplit = $split->with('b', null);
        self::assertEquals([], $newSplit->toArray(), 'The split config should have inherited CLEAN_ALL flag');
    }

    public function testArrayAccessImmutable(): void
    {
        $c = new Config();
        self::assertTrue(isset($c['a.b']) === false);

        $this->expectException(\LogicException::class);
        $c['a.b'] = 'value'; // Should throw
    }

    public function testArrayAccessUnsetImmutable(): void
    {
        $c = new Config(['a' => 1]);
        $this->expectException(\LogicException::class);
        unset($c['a']); // Should throw
    }

    public function testCount(): void
    {
        $c = new Config(['a' => ['b' => 'c'], 'd' => [1, 2, 3], 'e' => 'f']);
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
        self::assertEquals(['a' => 1, 'b' => 2], $items);
    }

    public function testSerialization(): void
    {
        $c = new Config(['a' => 1], Config::CLEAN_ALL);
        $serialized = serialize($c);
        /** @var Config $unserialized */
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Config::class, $unserialized);
        self::assertEquals($c->toArray(), $unserialized->toArray());

        // Verify that the cleaning flag was restored
        $newC = $unserialized->with('b', []);
        self::assertEquals(['a' => 1], $newC->toArray(), 'Flag should be restored after unserialization');
    }

    public function testOffsetGetWithIntegerOffset(): void
    {
        $config = new Config([0 => 'first', 1 => 'second']);
        self::assertEquals('first', $config[0]);
        self::assertEquals('second', $config[1]);
    }

    // public function testWrapWithNull(): void
    // {
    //     self::assertEquals([], Config::wrap(null));
    // }

    /**
     * @covers ::wrap
     */
    public function testAppendWithNullValue(): void
    {
        // This test indirectly verifies that `wrap(null)` behaves as expected (returns an empty array).
        // By appending `null`, we are testing the case where `wrap` receives null.
        // The expected result is that the key is set with an empty array, because
        // `array_merge(wrap($original_non_existent), wrap(null))` becomes `array_merge([], [])`.
        $c = new Config();
        $newC = $c->append('a.b', null);

        self::assertEquals(['a' => ['b' => []]], $newC->toArray());
    }

    public function testCleanEmptyArrays(): void
    {
        $config = new Config([
            'keep' => 'value',
            'empty' => [],
            'nested' => ['inner_empty' => [], 'inner_value' => 'test']
        ], Config::CLEAN_ALL);

        $expected = [
            'keep' => 'value',
            'nested' => ['inner_value' => 'test']
        ];

        self::assertEquals($expected, $config->toArray());
    }
}
