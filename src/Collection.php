<?php

namespace YorCreative\ArgonautDTO;

use ArrayAccess;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A deliberately small, dependency-free collection used by Argonaut.
 *
 * It provides the operations DTO consumers normally need without requiring
 * Illuminate or another collection framework.
 *
 * @template TValue
 *
 * @implements ArrayAccess<int|string, TValue>
 * @implements IteratorAggregate<int|string, TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int|string, TValue> */
    protected array $items;

    /** @param iterable<int|string, TValue> $items */
    public function __construct(iterable $items = [])
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items);
    }

    /** @return array<int|string, TValue> */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @template TMapped
     *
     * @param  callable(TValue, int|string): TMapped  $callback
     * @return static<TMapped>
     */
    public function map(callable $callback): static
    {
        $mapped = [];

        foreach ($this->items as $key => $item) {
            $mapped[$key] = $callback($item, $key);
        }

        return new static($mapped);
    }

    /**
     * @param  (callable(TValue, int|string): bool)|null  $callback
     */
    public function filter(?callable $callback = null): static
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @template TDefault
     *
     * @param  TDefault  $default
     * @return TValue|TDefault
     */
    public function first(mixed $default = null): mixed
    {
        foreach ($this->items as $item) {
            return $item;
        }

        return $default;
    }

    /**
     * @template TReduce
     *
     * @param  callable(TReduce, TValue, int|string): TReduce  $callback
     * @param  TReduce  $initial
     * @return TReduce
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $accumulator = $initial;

        foreach ($this->items as $key => $item) {
            $accumulator = $callback($accumulator, $item, $key);
        }

        return $accumulator;
    }

    /**
     * @template TDefault
     *
     * @param  TDefault  $default
     * @return TValue|TDefault
     */
    public function last(mixed $default = null): mixed
    {
        if ($this->items === []) {
            return $default;
        }

        return $this->items[array_key_last($this->items)];
    }

    /**
     * Accepts either a value compared strictly, or a predicate.
     *
     * @param  TValue|callable(TValue, int|string): bool  $value
     */
    public function contains(mixed $value): bool
    {
        if (! $value instanceof Closure) {
            return in_array($value, $this->items, true);
        }

        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return true;
            }
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    /** @return Traversable<int|string, TValue> */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /** @return TValue|null */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /** @param TValue $value */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;

            return;
        }

        $this->items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /** @return array<int|string, mixed> */
    public function jsonSerialize(): array
    {
        return array_map(static function (mixed $value): mixed {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }

            return $value;
        }, $this->items);
    }
}
