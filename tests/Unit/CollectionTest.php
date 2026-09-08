<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\TagDTO;

final class CollectionTest extends TestCase
{
    public function test_it_accepts_an_array(): void
    {
        self::assertSame(['a', 'b'], (new Collection(['a', 'b']))->all());
    }

    public function test_it_accepts_any_traversable(): void
    {
        $collection = new Collection(new ArrayIterator(['x' => 1, 'y' => 2]));

        self::assertSame(['x' => 1, 'y' => 2], $collection->all());
    }

    public function test_it_defaults_to_empty(): void
    {
        self::assertSame([], (new Collection)->all());
    }

    public function test_map_preserves_keys_and_returns_a_new_collection(): void
    {
        $original = new Collection(['a' => 1, 'b' => 2]);
        $mapped = $original->map(fn (int $value): int => $value * 10);

        self::assertSame(['a' => 10, 'b' => 20], $mapped->all());
        self::assertSame(['a' => 1, 'b' => 2], $original->all(), 'map() must not mutate the source');
    }

    public function test_map_receives_the_key_as_a_second_argument(): void
    {
        $mapped = (new Collection(['a' => 1]))->map(
            fn (int $value, string $key): string => $key.$value,
        );

        self::assertSame(['a' => 'a1'], $mapped->all());
    }

    public function test_filter_uses_both_value_and_key(): void
    {
        $filtered = (new Collection(['keep' => 1, 'drop' => 2]))->filter(
            fn (int $value, string $key): bool => $key === 'keep',
        );

        self::assertSame(['keep' => 1], $filtered->all());
    }

    public function test_filter_without_a_callback_removes_falsy_values(): void
    {
        $filtered = (new Collection([1, 0, 2, null, 3, '']))->filter();

        self::assertSame([1, 2, 3], $filtered->values()->all());
    }

    public function test_first_returns_the_first_item(): void
    {
        self::assertSame('a', (new Collection(['a', 'b']))->first());
    }

    public function test_first_returns_the_default_when_empty(): void
    {
        self::assertSame('fallback', (new Collection)->first('fallback'));
        self::assertNull((new Collection)->first());
    }

    public function test_is_empty_and_is_not_empty(): void
    {
        self::assertTrue((new Collection)->isEmpty());
        self::assertFalse((new Collection)->isNotEmpty());
        self::assertFalse((new Collection([1]))->isEmpty());
        self::assertTrue((new Collection([1]))->isNotEmpty());
    }

    public function test_values_reindexes_keys(): void
    {
        self::assertSame([1, 2], (new Collection([5 => 1, 9 => 2]))->values()->all());
    }

    public function test_it_is_countable(): void
    {
        self::assertCount(2, new Collection(['a', 'b']));
        self::assertCount(0, new Collection);
    }

    public function test_it_is_iterable_and_yields_keys(): void
    {
        $seen = [];

        foreach (new Collection(['a' => 1, 'b' => 2]) as $key => $value) {
            $seen[$key] = $value;
        }

        self::assertSame(['a' => 1, 'b' => 2], $seen);
    }

    public function test_array_access_reads_and_writes(): void
    {
        $collection = new Collection(['a' => 1]);

        self::assertSame(1, $collection['a']);
        self::assertTrue(isset($collection['a']));

        $collection['b'] = 2;
        self::assertSame(2, $collection['b']);

        unset($collection['a']);
        self::assertFalse(isset($collection['a']));
        self::assertSame(['b' => 2], $collection->all());
    }

    public function test_appending_with_a_null_offset_pushes(): void
    {
        $collection = new Collection([1]);
        $collection[] = 2;

        self::assertSame([1, 2], $collection->all());
    }

    public function test_offset_get_returns_null_for_a_missing_key(): void
    {
        self::assertNull((new Collection)['nope']);
    }

    public function test_json_serialize_unwraps_nested_dtos(): void
    {
        $collection = new Collection([new TagDTO(['name' => 'php'])]);

        self::assertSame('[{"name":"php"}]', (string) json_encode($collection));
    }

    public function test_json_serialize_leaves_scalars_untouched(): void
    {
        self::assertSame([1, 'two'], (new Collection([1, 'two']))->jsonSerialize());
    }

    public function test_reduce_folds_items_into_an_accumulator(): void
    {
        $sum = (new Collection([1, 2, 3]))->reduce(
            static fn (int $carry, int $item): int => $carry + $item,
            0
        );

        self::assertSame(6, $sum);
    }

    public function test_reduce_returns_the_initial_value_when_empty(): void
    {
        self::assertSame(9, (new Collection)->reduce(static fn (int $c, mixed $i): int => $c + 1, 9));
    }

    public function test_reduce_passes_the_key_as_the_third_argument(): void
    {
        $keys = (new Collection(['a' => 1, 'b' => 2]))->reduce(
            static fn (array $carry, int $item, string|int $key): array => [...$carry, $key],
            []
        );

        self::assertSame(['a', 'b'], $keys);
    }

    public function test_last_returns_the_final_item(): void
    {
        self::assertSame('c', (new Collection(['a', 'b', 'c']))->last());
    }

    public function test_last_returns_the_default_when_empty(): void
    {
        self::assertSame('fallback', (new Collection)->last('fallback'));
    }

    public function test_contains_finds_a_value_strictly(): void
    {
        $collection = new Collection([1, 2, 3]);

        self::assertTrue($collection->contains(2));
        self::assertFalse($collection->contains('2'));
    }

    public function test_contains_accepts_a_predicate(): void
    {
        $collection = new Collection([1, 2, 3]);

        self::assertTrue($collection->contains(static fn (int $item): bool => $item > 2));
        self::assertFalse($collection->contains(static fn (int $item): bool => $item > 9));
    }
}
